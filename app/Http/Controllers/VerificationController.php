<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyVerification;
use App\Services\VerificationService;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(
        private VerificationService $verificationService
    ) {}

    /**
     * List verifications (for user, agent, or admin)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Admin sees all
        if ($user->hasRole(['admin', 'super_admin'])) {
            $verifications = PropertyVerification::with(['property', 'agent'])
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()
                ->paginate(20);

            return view('pages.verifications.index', compact('verifications'));
        }

        // Agent sees assigned jobs
        if ($user->is_agent) {
            $verifications = PropertyVerification::where('agent_id', $user->id)
                ->with(['property.media'])
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()
                ->paginate(20);

            return view('pages.agent.jobs', compact('verifications'));
        }

        // Owner sees verifications of their properties
        $verifications = PropertyVerification::whereHas('property', function ($q) use ($user) {
            $q->where('owner_id', $user->id);
        })
            ->with('property')
            ->latest()
            ->paginate(20);

        return view('pages.verifications.index', compact('verifications'));
    }

    /**
     * Request verification for a property
     */
    public function request(Request $request, Property $property)
    {
        // Only owner can request
        if ($property->owner_id !== auth()->id() && !auth()->user()->hasRole(['admin', 'super_admin'])) {
            abort(403, 'You can only request verification for your own properties.');
        }

        if ($property->verification_status === 'verified') {
            return back()->with('info', 'This property is already verified.');
        }

        if ($property->verifications()->whereIn('status', ['scheduled', 'assigned', 'in_progress'])->exists()) {
            return back()->with('info', 'A verification is already in progress.');
        }

        $validated = $request->validate([
            'urgency' => 'nullable|in:normal,urgent',
        ]);

        try {
            $verification = $this->verificationService->requestVerification($property, $validated);

            return redirect()
                ->route('verifications.show', $verification)
                ->with('success', 'Verification requested! An agent will be assigned shortly.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show a verification
     */
    public function show(PropertyVerification $verification)
    {
        $user = auth()->user();

        // Authorization: owner, agent, or admin
        $canView = $user->id === $verification->property->owner_id
            || $user->id === $verification->agent_id
            || $user->hasRole(['admin', 'super_admin']);

        if (!$canView) {
            abort(403);
        }

        $verification->load([
            'property.media',
            'property.owner',
            'agent.agentProfile',
        ]);

        return view('pages.verifications.show', compact('verification'));
    }

    /**
     * Agent accepts a job
     */
    public function accept(PropertyVerification $verification)
    {
        $user = auth()->user();

        if ($verification->agent_id !== $user->id) {
            abort(403, 'This job is not assigned to you.');
        }

        try {
            $this->verificationService->acceptJob($verification, $user);

            return redirect()
                ->route('verifications.show', $verification)
                ->with('success', 'Job accepted. Head to the property when ready.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Agent checks in at property
     */
    public function checkIn(Request $request, PropertyVerification $verification)
    {
        if ($verification->agent_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        try {
            $this->verificationService->arriveAtProperty(
                $verification,
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );

            return response()->json(['success' => true, 'message' => 'Checked in at property.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Agent completes verification
     */
    public function complete(Request $request, PropertyVerification $verification)
    {
        if ($verification->agent_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'agent_report' => 'required|array',
            'agent_report.condition_rating' => 'required|integer|min:1|max:5',
            'agent_report.condition_notes' => 'required|string|max:2000',
            'agent_report.ownership_confirmed' => 'required|boolean',
            'agent_report.documents_authentic' => 'required|boolean',
            'agent_report.property_matches_listing' => 'required|boolean',
            'agent_photos' => 'required|array|min:4',
            'agent_photos.*' => 'url|string|max:500',
            'agent_notes' => 'nullable|string|max:2000',
            'documents' => 'nullable|array',
        ]);

        try {
            $this->verificationService->completeVerification($verification, $validated);

            return redirect()
                ->route('agent.dashboard')
                ->with('success', 'Verification completed! Payment will be processed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Admin reviews a verification
     */
    public function review(Request $request, PropertyVerification $verification)
    {
        if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
            abort(403);
        }

        $validated = $request->validate([
            'approved' => 'required|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->verificationService->reviewVerification(
                $verification,
                auth()->user(),
                (bool) $validated['approved'],
                $validated['notes'] ?? null
            );

            return back()->with('success', 'Verification reviewed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}