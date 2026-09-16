<?php

namespace App\Http\Controllers;

use App\Models\PropertyVerification;
use App\Models\AgentProfile;
use App\Services\AgentService;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    public function __construct(
        private AgentService $agentService,
        private VerificationService $verificationService,
    ) {}

    /**
     * Agent dashboard
     */
    public function dashboard()
    {
        $agent = auth()->user();
        $profile = $agent->agentProfile;

        $stats = [
            'available_jobs' => PropertyVerification::where('status', 'scheduled')
                ->whereNull('agent_id')
                ->count(),
            'in_progress' => PropertyVerification::where('agent_id', $agent->id)
                ->whereIn('status', ['assigned', 'accepted', 'in_progress', 'at_property'])
                ->count(),
            'completed_30d' => PropertyVerification::where('agent_id', $agent->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subDays(30))
                ->count(),
            'earnings_30d' => PropertyVerification::where('agent_id', $agent->id)
                ->where('status', 'completed')
                ->where('completed_at', '>=', now()->subDays(30))
                ->sum('total_earnings'),
        ];

        $currentJob = PropertyVerification::where('agent_id', $agent->id)
            ->whereIn('status', ['assigned', 'accepted', 'in_progress', 'at_property'])
            ->with(['property.media'])
            ->latest()
            ->first();

        $recentJobs = PropertyVerification::where('agent_id', $agent->id)
            ->where('status', 'completed')
            ->with('property')
            ->latest('completed_at')
            ->limit(5)
            ->get();

        return view('pages.agent.dashboard', compact('stats', 'currentJob', 'recentJobs'));
    }

    /**
     * Job queue
     */
    public function jobs(Request $request)
    {
        $agent = auth()->user();

        $query = PropertyVerification::query()
            ->with(['property.media', 'property.owner'])
            ->when($request->status === 'available', function ($q) {
                $q->whereNull('agent_id')->where('status', 'scheduled');
            })
            ->when($request->status === 'assigned', function ($q) use ($agent) {
                $q->where('agent_id', $agent->id)->whereIn('status', ['assigned', 'accepted']);
            })
            ->when($request->status === 'in_progress', function ($q) use ($agent) {
                $q->where('agent_id', $agent->id)->whereIn('status', ['in_progress', 'at_property']);
            })
            ->when($request->status === 'completed', function ($q) use ($agent) {
                $q->where('agent_id', $agent->id)->where('status', 'completed');
            })
            ->when(!$request->status, function ($q) use ($agent) {
                // Default: show available + assigned to me
                $q->where(function ($sub) use ($agent) {
                    $sub->where(function ($s) {
                        $s->whereNull('agent_id')->where('status', 'scheduled');
                    })->orWhere(function ($s) use ($agent) {
                        $s->where('agent_id', $agent->id)
                            ->whereIn('status', ['assigned', 'accepted', 'in_progress']);
                    });
                });
            })
            ->when($request->neighborhood, fn ($q, $n) => $q->whereHas('property', fn ($p) => $p->where('neighborhood', $n)));

        // If agent has GPS, order by proximity
        if ($agent->gps_location) {
            $lat = $agent->gps_location->getLat();
            $lng = $agent->gps_location->getLng();
            $query->join('properties', 'property_verifications.property_id', '=', 'properties.id')
                ->select('property_verifications.*')
                ->orderByRaw("ST_Distance_Sphere(properties.location, POINT(?, ?))", [$lat, $lng]);
        } else {
            $query->orderBy('scheduled_at', 'asc');
        }

        $jobs = $query->paginate(20);

        $neighborhoods = Property::where('status', 'active')
            ->distinct()
            ->pluck('neighborhood');

        return view('pages.agent.jobs', compact('jobs', 'neighborhoods'));
    }

    /**
     * Earnings
     */
    public function earnings(Request $request)
    {
        $agent = auth()->user();

        // Current month
        $thisMonth = $this->verificationService->getAgentEarnings(
            $agent,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Last month
        $lastMonth = $this->verificationService->getAgentEarnings(
            $agent,
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        );

        // 6-month chart
        $monthlyEarnings = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $earnings = PropertyVerification::where('agent_id', $agent->id)
                ->where('status', 'completed')
                ->whereYear('completed_at', $month->year)
                ->whereMonth('completed_at', $month->month)
                ->sum('total_earnings');

            $monthlyEarnings[$month->format('M Y')] = $earnings;
        }

        // Recent transactions
        $transactions = PropertyVerification::where('agent_id', $agent->id)
            ->where('status', 'completed')
            ->with('property')
            ->latest('completed_at')
            ->limit(20)
            ->get()
            ->map(fn ($job) => (object) [
                'job_number' => $job->job_number,
                'amount' => $job->total_earnings,
                'status' => 'completed',
                'created_at' => $job->completed_at,
            ]);

        return view('pages.agent.earnings', compact(
            'thisMonth', 'lastMonth', 'monthlyEarnings', 'transactions'
        ));
    }

    /**
     * Agent profile
     */
    public function profile()
    {
        $agent = auth()->user()->load('agentProfile');

        return view('pages.agent.profile', compact('agent'));
    }

    /**
     * Update agent profile
     */
    public function updateProfile(Request $request)
    {
        $agent = auth()->user();

        $validated = $request->validate([
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $agent->id,
            'coverage_area' => 'nullable|string|max:255',
            'specializations' => 'nullable|array',
            'specializations.*' => 'string|max:50',
            'is_available' => 'nullable|boolean',
        ]);

        $agent->update([
            'phone' => $validated['phone'] ?? $agent->phone,
        ]);

        if ($agent->agentProfile) {
            $agent->agentProfile->update([
                'coverage_area' => $validated['coverage_area'] ?? $agent->agentProfile->coverage_area,
                'specializations' => $validated['specializations'] ?? $agent->agentProfile->specializations,
                'is_available' => $request->boolean('is_available'),
            ]);
        }

        return back()->with('success', 'Profile updated.');
    }

    /**
     * Toggle availability
     */
    public function toggleAvailability()
    {
        $agent = auth()->user();
        $profile = $agent->agentProfile;

        if (!$profile) {
            return back()->with('error', 'Agent profile not found.');
        }

        $profile->update(['is_available' => !$profile->is_available]);

        return back()->with('success', $profile->is_available
            ? 'You are now available for new jobs.'
            : 'You are no longer accepting new jobs.');
    }
}