<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\MarketMetric;
use App\Models\User;
use App\Models\Escrow;
use App\Models\AVMValuation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Homepage
     */
    public function index()
    {
        $stats = Cache::remember('home_stats', 3600, function () {
            return [
                'verified_properties' => number_format(Property::where('verification_status', 'verified')->count()),
                'certified_agents' => number_format(User::whereIn('role', ['agent_certified', 'agent_premier', 'agent_master'])->count()),
                'transactions' => number_format(Escrow::where('status', 'completed')->count()),
                'avm_accuracy' => number_format(
                    AVMValuation::where('validation_status', 'validated')->avg('accuracy_pct') ?? 92.4,
                    1
                ),
            ];
        });

        $featuredProperties = Property::query()
            ->where('status', 'active')
            ->where('verification_status', 'verified')
            ->where(function ($q) {
                $q->where('is_featured', true)
                    ->where(function ($sub) {
                        $sub->whereNull('featured_until')->orWhere('featured_until', '>', now());
                    });
            })
            ->with('media')
            ->orderBy('view_count', 'desc')
            ->limit(6)
            ->get();

        // If not enough featured, fill with recent verified
        if ($featuredProperties->count() < 6) {
            $additional = Property::where('status', 'active')
                ->where('verification_status', 'verified')
                ->whereNotIn('id', $featuredProperties->pluck('id'))
                ->with('media')
                ->latest()
                ->limit(6 - $featuredProperties->count())
                ->get();

            $featuredProperties = $featuredProperties->concat($additional);
        }

        $marketHighlights = Cache::remember('market_highlights', 3600, function () {
            return MarketMetric::query()
                ->where('metric_date', '>=', now()->subDays(7))
                ->whereIn('neighborhood', ['Bole', 'CMC', 'Mekanisa', 'Sarbet'])
                ->orderBy('metric_date', 'desc')
                ->get()
                ->unique('neighborhood')
                ->map(fn ($m) => [
                    'neighborhood' => $m->neighborhood,
                    'avg_price_per_sqm' => $m->avg_price_per_sqm,
                    'change' => $m->price_change_30d ?? 0,
                    'active_listings' => $m->active_listings,
                ])
                ->values()
                ->toArray();
        });

        return view('pages.home', compact('stats', 'featuredProperties', 'marketHighlights'));
    }

    /**
     * About page
     */
    public function about()
    {
        return view('pages.about');
    }

    /**
     * Contact page
     */
    public function contact()
    {
        return view('pages.contact');
    }

    /**
     * Submit contact form
     */
    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:100',
            'message' => 'required|string|min:20|max:5000',
        ]);

        // Log the contact form submission
        \Log::info('Contact form submitted', [
            'from' => $validated['email'],
            'subject' => $validated['subject'],
            'ip' => $request->ip(),
        ]);

        // Send email to support
        try {
            \Mail::to(config('mail.from.address'))->send(
                new \App\Mail\ContactFormMail($validated)
            );
        } catch (\Exception $e) {
            \Log::error('Contact form email failed', ['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Thanks for reaching out! We\'ll respond within 24 hours.');
    }

    /**
     * Privacy policy
     */
    public function privacy()
    {
        return view('pages.privacy');
    }

    /**
     * Terms of service
     */
    public function terms()
    {
        return view('pages.terms');
    }
}