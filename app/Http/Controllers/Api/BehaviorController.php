<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserBehaviorSummary;
use App\Services\AI\UserBehaviorService;
use Illuminate\Http\Request;

class BehaviorController extends Controller
{
    public function __construct(
        private UserBehaviorService $behaviorService
    ) {}

    /**
     * Track behavior events (batch)
     * POST /api/v1/behavior/track
     */
    public function track(Request $request)
    {
        $validated = $request->validate([
            'events' => 'required|array|max:50',
            'events.*.event_type' => 'required|string|max:100',
            'events.*.session_id' => 'required|string|max:255',
            'events.*.url' => 'nullable|string|max:500',
            'events.*.path' => 'nullable|string|max:255',
            'events.*.timestamp' => 'nullable|date',
            'events.*.property_id' => 'nullable|integer',
            'events.*.search_query' => 'nullable|string|max:255',
            'events.*.time_on_page' => 'nullable|integer|min:0',
            'events.*.scroll_depth' => 'nullable|integer|min:0|max:100',
        ]);

        $user = auth()->user();

        // Add IP and user agent to all events
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        $count = 0;
        foreach ($validated['events'] as $eventData) {
            try {
                $this->behaviorService->logEvent(
                    $user,
                    $eventData['session_id'],
                    $eventData['event_type'],
                    array_merge($eventData, [
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                    ])
                );
                $count++;
            } catch (\Exception $e) {
                \Log::warning('Behavior event failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'tracked' => $count,
        ]);
    }

    /**
     * Get behavior summary for current user
     * GET /api/v1/behavior/summary
     */
    public function summary()
    {
        $user = auth()->user();

        $summary = UserBehaviorSummary::where('user_id', $user->id)
            ->latest('summary_date')
            ->first();

        if (!$summary) {
            // Compute on the fly
            $summary = $this->behaviorService->updateDailySummary($user);
        }

        return response()->json([
            'summary_date' => $summary->summary_date,
            'engagement_score' => $summary->engagement_score,
            'intent_score' => $summary->intent_score,
            'churn_risk_score' => $summary->churn_risk_score,
            'funnel_stage' => $summary->funnel_stage,
            'sessions_count' => $summary->sessions_count,
            'properties_viewed' => $summary->properties_viewed,
            'properties_saved' => $summary->properties_saved,
            'properties_inquired' => $summary->properties_inquired,
            'preferences' => [
                'neighborhoods' => $summary->preferred_neighborhoods ?? [],
                'property_types' => $summary->preferred_property_types ?? [],
                'price_range' => [
                    'min' => $summary->price_range_min,
                    'max' => $summary->price_range_max,
                ],
            ],
        ]);
    }

    /**
     * Get personalized recommendations
     * GET /api/v1/behavior/recommendations
     */
    public function recommendations()
    {
        $user = auth()->user();

        $properties = $this->behaviorService->getRecommendations($user);

        return response()->json([
            'recommendations' => $properties,
        ]);
    }

    /**
     * Get conversion funnel (admin only)
     * GET /api/v1/behavior/funnel
     */
    public function funnel(Request $request)
    {
        if (!auth()->user()->hasRole(['admin', 'super_admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $period = $request->query('period', '7d');
        $funnel = $this->behaviorService->getConversionFunnel($period);

        return response()->json($funnel);
    }
}