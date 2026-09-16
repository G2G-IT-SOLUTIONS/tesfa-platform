<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Services\AI\AVMService;
use App\Services\AI\FraudDetectionService;
use App\Services\AI\UserBehaviorService;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PropertyController extends Controller
{
    public function __construct(
        private AVMService $avmService,
        private FraudDetectionService $fraudService,
        private UserBehaviorService $behaviorService,
    ) {}

    /**
     * List properties with filters
     */
    public function index(Request $request)
    {
        $cacheKey = 'properties:' . md5(json_encode($request->all()));

        $properties = Property::query()
                ->active()
                ->with(['owner:id,name,email,created_at', 'agent:id,name,email'])
                ->when($request->neighborhood, fn ($q, $v) => $q->where('neighborhood', $v))
                ->when($request->type, fn ($q, $v) => $q->where('type', $v))
                ->when($request->purpose, fn ($q, $v) => $q->where('purpose', $v))
                ->when($request->bedrooms, fn ($q, $v) => $q->where('bedrooms', '>=', $v))
                ->when($request->bathrooms, fn ($q, $v) => $q->where('bathrooms', '>=', $v))
                ->when($request->min_price, fn ($q, $v) => $q->where('price', '>=', $v))
                ->when($request->max_price, fn ($q, $v) => $q->where('price', '<=', $v))
                ->when($request->verified, fn ($q) => $q->verified())
                ->when($request->rent_to_own, fn ($q) => $q->rentToOwn())
                ->when($request->short_term, fn ($q) => $q->where('short_term_enabled', true))
                ->when($request->search, function ($q, $v) {
                    $q->where(function ($q) use ($v) {
                        $q->where('title', 'LIKE', "%{$v}%")
                            ->orWhere('description', 'LIKE', "%{$v}%")
                            ->orWhere('address', 'LIKE', "%{$v}%");
                    });
                })
                ->when($request->lat && $request->lng, function ($q) use ($request) {
                    $q->nearby($request->lat, $request->lng, $request->radius ?? 5);
                })
                ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc')
                ->paginate(20);
        

        // Track search behavior
        if (Auth::check()) {
            $this->behaviorService->logEvent(
                Auth::user(),
                session()->getId(),
                'search',
                [
                    'search_query' => $request->search,
                    'search_filters' => $request->except(['page', 'sort_by', 'sort_dir']),
                    'search_results_count' => $properties->total(),
                ]
            );
        }

        return view('pages.properties.index', compact('properties'));
    }

    /**
     * Show property details
     */
    public function show(Property $property)
    {
        $property->load([
            'owner:id,name,email,created_at',
            'agent:id,name,email',
            'agent.agentProfile',
            'latestVerification.agent',
            'latestValuation',
            'media',
        ]);

        // Increment view count
        $property->incrementViewCount();

        // Track view behavior
        if (Auth::check()) {
            $this->behaviorService->logEvent(
                Auth::user(),
                session()->getId(),
                'page_view',
                [
                    'property_id' => $property->id,
                    'property_type' => $property->type,
                    'property_price' => $property->price,
                    'property_neighborhood' => $property->neighborhood,
                ]
            );
        }

        // Get similar properties
        $similarProperties = Property::active()
            ->where('id', '!=', $property->id)
            ->where('neighborhood', $property->neighborhood)
            ->where('type', $property->type)
            ->priceRange($property->price * 0.8, $property->price * 1.2)
            ->limit(4)
            ->get();

        // Get valuation
        $valuation = $property->latestValuation ?? $this->avmService->valuate($property);

        return view('pages.properties.show', compact('property', 'similarProperties', 'valuation'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $this->authorize('create', Property::class);

        return view('pages.properties.create');
    }

    /**
     * Store new property
     */
    public function store(StorePropertyRequest $request)
    {
        $this->authorize('create', Property::class);

        $data = $request->validated();
        $data['owner_id'] = Auth::id();
        $data['status'] = 'draft';

        $property = Property::create($data);

        // Handle photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $property->addMedia($photo)->toMediaCollection('photos');
            }
        }

        // Handle document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $type => $document) {
                $property->addMedia($document)->toMediaCollection("document_{$type}");
            }
        }

        // Run fraud scan
        $fraudAlert = $this->fraudService->scanProperty($property);

        if ($fraudAlert && $fraudAlert->severity === 'critical') {
            return redirect()
                ->route('properties.show', $property)
                ->with('warning', 'Property created but flagged for review. Our team will contact you.');
        }

        // Fire event
        event(new \App\Events\PropertyListed($property));

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'Property created successfully!');
    }

    /**
     * Show edit form
     */
    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        return view('pages.properties.edit', compact('property'));
    }

    /**
     * Update property
     */
    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $this->authorize('update', $property);

        $property->update($request->validated());

        // Handle new photos
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $property->addMedia($photo)->toMediaCollection('photos');
            }
        }

        return redirect()
            ->route('properties.show', $property)
            ->with('success', 'Property updated successfully!');
    }

    /**
     * Delete property
     */
    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);

        $property->delete();

        return redirect()
            ->route('my-properties')
            ->with('success', 'Property deleted successfully!');
    }

    /**
     * Submit inquiry
     */
    public function inquiry(Request $request, Property $property)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'message' => 'required|string|max:1000',
        ]);

        $property->incrementInquiryCount();

        // Send notification to owner
        $property->owner->notify(new \App\Notifications\PropertyInquiryNotification($property, $validated));

        // Track behavior
        if (Auth::check()) {
            $this->behaviorService->logEvent(
                Auth::user(),
                session()->getId(),
                'contact_agent',
                ['property_id' => $property->id]
            );
        }

        return back()->with('success', 'Your inquiry has been sent!');
    }

    /**
     * Save property
     */
    public function save(Property $property)
    {
        $user = Auth::user();

        if ($user->savedProperties()->where('property_id', $property->id)->exists()) {
            $user->savedProperties()->detach($property->id);
            $message = 'Property removed from saved.';
        } else {
            $user->savedProperties()->attach($property->id);
            $property->increment('save_count');
            $message = 'Property saved!';

            $this->behaviorService->logEvent(
                $user,
                session()->getId(),
                'save_property',
                ['property_id' => $property->id]
            );
        }

        return back()->with('success', $message);
    }

    /**
     * My properties
     */
    public function myProperties()
    {
        $properties = Auth::user()
            ->properties()
            ->with(['latestValuation', 'latestVerification'])
            ->latest()
            ->paginate(10);

        return view('pages.properties.my-properties', compact('properties'));
    }
}