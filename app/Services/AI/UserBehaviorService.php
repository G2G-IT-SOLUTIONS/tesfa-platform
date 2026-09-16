<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\UserBehaviorLog;
use App\Models\UserBehaviorSummary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UserBehaviorService
{
    /**
     * Log a user behavior event
     */
    public function logEvent(?User $user, string $sessionId, string $eventType, array $context = []): UserBehaviorLog
    {
        $log = UserBehaviorLog::create([
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'event_type' => $eventType,
            'event_name' => $context['name'] ?? $eventType,
            'page_url' => $context['url'] ?? request()->url(),
            'page_path' => $context['path'] ?? request()->path(),
            'referrer' => $context['referrer'] ?? request()->header('referer'),
            'property_id' => $context['property_id'] ?? null,
            'property_type' => $context['property_type'] ?? null,
            'property_price' => $context['property_price'] ?? null,
            'property_neighborhood' => $context['property_neighborhood'] ?? null,
            'search_query' => $context['search_query'] ?? null,
            'search_filters' => $context['search_filters'] ?? null,
            'search_results_count' => $context['search_results_count'] ?? null,
            'element_id' => $context['element_id'] ?? null,
            'element_text' => $context['element_text'] ?? null,
            'element_type' => $context['element_type'] ?? null,
            'time_on_page' => $context['time_on_page'] ?? null,
            'time_on_element' => $context['time_on_element'] ?? null,
            'scroll_depth' => $context['scroll_depth'] ?? null,
            'device_type' => $this->getDeviceType(),
            'browser' => $this->getBrowser(),
            'os' => $this->getOs(),
            'screen_resolution' => $context['screen_resolution'] ?? null,
            'ip_address' => request()->ip(),
            'country' => $context['country'] ?? 'Ethiopia',
            'city' => $context['city'] ?? null,
            'conversion_value' => $context['conversion_value'] ?? null,
            'conversion_type' => $context['conversion_type'] ?? null,
        ]);

        return $log;
    }

    /**
     * Compute engagement score for a user (RFM-style)
     */
    public function computeEngagementScore(User $user): float
    {
        $recency = $this->computeRecencyScore($user);
        $frequency = $this->computeFrequencyScore($user);
        $depth = $this->computeDepthScore($user);

        // Weighted average
        return round(($recency * 0.3) + ($frequency * 0.4) + ($depth * 0.3), 2);
    }

    /**
     * Compute intent score (likelihood to purchase)
     */
    public function computeIntentScore(User $user): float
    {
        $recentEvents = UserBehaviorLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($recentEvents->isEmpty()) {
            return 0;
        }

        $score = 0;

        // High-intent events
        $highIntentEvents = [
            'purchase_intent' => 30,
            'contact_agent' => 25,
            'form_submit' => 20,
            'save_property' => 15,
            'virtual_tour' => 10,
            'share_property' => 8,
            'search' => 5,
            'page_view' => 2,
        ];

        foreach ($recentEvents as $event) {
            $score += $highIntentEvents[$event->event_type] ?? 0;
        }

        // Normalize to 0-100
        return round(min(100, $score), 2);
    }

    /**
     * Compute churn risk score
     */
    public function computeChurnRisk(User $user): float
    {
        $lastActivity = UserBehaviorLog::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (!$lastActivity) {
            return 100; // Never active
        }

        $daysSinceActivity = $lastActivity->created_at->diffInDays(now());

        // Risk increases with inactivity
        $risk = min(100, $daysSinceActivity * 5);

        // Adjust based on engagement
        $engagement = $this->computeEngagementScore($user);
        $risk = $risk * (1 - ($engagement / 200));

        return round(max(0, min(100, $risk)), 2);
    }

    /**
     * Get user journey (funnel visualization)
     */
    public function getUserJourney(User $user): array
    {
        $events = UserBehaviorLog::where('user_id', $user->id)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($e) => $e->created_at->format('Y-m-d'));

        $journey = [];
        foreach ($events as $date => $dayEvents) {
            $journey[] = [
                'date' => $date,
                'events' => $dayEvents->groupBy('event_type')->map->count()->toArray(),
                'total' => $dayEvents->count(),
            ];
        }

        return $journey;
    }

    /**
     * Get recommendations for user
     */
    public function getRecommendations(User $user): array
    {
        $summary = UserBehaviorSummary::where('user_id', $user->id)
            ->latest('summary_date')
            ->first();

        if (!$summary) {
            return [];
        }

        $preferredNeighborhoods = $summary->preferred_neighborhoods ?? [];
        $preferredTypes = $summary->preferred_property_types ?? [];
        $priceMin = $summary->price_range_min ?? 0;
        $priceMax = $summary->price_range_max ?? PHP_INT_MAX;

        return \App\Models\Property::query()
            ->where('status', 'active')
            ->when($preferredNeighborhoods, fn ($q) => $q->whereIn('neighborhood', $preferredNeighborhoods))
            ->when($preferredTypes, fn ($q) => $q->whereIn('type', $preferredTypes))
            ->whereBetween('price', [$priceMin, $priceMax])
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get conversion funnel
     */
    public function getConversionFunnel(string $period = '7d'): array
    {
        $days = (int) str_replace('d', '', $period);
        $startDate = now()->subDays($days);

        $visitors = UserBehaviorLog::where('created_at', '>=', $startDate)
            ->distinct('session_id')
            ->count('session_id');

        $searchers = UserBehaviorLog::where('created_at', '>=', $startDate)
            ->where('event_type', 'search')
            ->distinct('user_id')
            ->count('user_id');

        $inquirers = UserBehaviorLog::where('created_at', '>=', $startDate)
            ->where('event_type', 'contact_agent')
            ->distinct('user_id')
            ->count('user_id');

        $buyers = UserBehaviorLog::where('created_at', '>=', $startDate)
            ->where('event_type', 'purchase_intent')
            ->distinct('user_id')
            ->count('user_id');

        return [
            'visitors' => $visitors,
            'searchers' => $searchers,
            'inquirers' => $inquirers,
            'buyers' => $buyers,
            'conversion_rates' => [
                'visitor_to_searcher' => $visitors > 0 ? round($searchers / $visitors * 100, 2) : 0,
                'searcher_to_inquirer' => $searchers > 0 ? round($inquirers / $searchers * 100, 2) : 0,
                'inquirer_to_buyer' => $inquirers > 0 ? round($buyers / $inquirers * 100, 2) : 0,
                'overall' => $visitors > 0 ? round($buyers / $visitors * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Compute recency score (0-100)
     */
    private function computeRecencyScore(User $user): float
    {
        $lastActivity = UserBehaviorLog::where('user_id', $user->id)
            ->latest('created_at')
            ->first();

        if (!$lastActivity) {
            return 0;
        }

        $daysSince = $lastActivity->created_at->diffInDays(now());

        return max(0, 100 - ($daysSince * 10));
    }

    /**
     * Compute frequency score (0-100)
     */
    private function computeFrequencyScore(User $user): float
    {
        $sessionsLast30Days = UserBehaviorLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct('session_id')
            ->count('session_id');

        return min(100, $sessionsLast30Days * 5);
    }

    /**
     * Compute depth score (0-100)
     */
    private function computeDepthScore(User $user): float
    {
        $avgTimeOnPage = UserBehaviorLog::where('user_id', $user->id)
            ->whereNotNull('time_on_page')
            ->avg('time_on_page') ?? 0;

        $avgScrollDepth = UserBehaviorLog::where('user_id', $user->id)
            ->whereNotNull('scroll_depth')
            ->avg('scroll_depth') ?? 0;

        $timeScore = min(50, $avgTimeOnPage / 2);
        $scrollScore = min(50, $avgScrollDepth / 2);

        return round($timeScore + $scrollScore, 2);
    }

    /**
     * Update daily summary for user
     */
    public function updateDailySummary(User $user): UserBehaviorSummary
    {
        $today = today();

        $events = UserBehaviorLog::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->get();

        $summary = UserBehaviorSummary::updateOrCreate(
            ['user_id' => $user->id, 'summary_date' => $today],
            [
                'sessions_count' => $events->distinct('session_id')->count('session_id'),
                'page_views_total' => $events->where('event_type', 'page_view')->count(),
                'searches_count' => $events->where('event_type', 'search')->count(),
                'properties_viewed' => $events->where('event_type', 'page_view')->whereNotNull('property_id')->count(),
                'properties_saved' => $events->where('event_type', 'save_property')->count(),
                'properties_shared' => $events->where('event_type', 'share_property')->count(),
                'properties_inquired' => $events->where('event_type', 'contact_agent')->count(),
                'engagement_score' => $this->computeEngagementScore($user),
                'intent_score' => $this->computeIntentScore($user),
                'churn_risk_score' => $this->computeChurnRisk($user),
                'funnel_stage' => $this->determineFunnelStage($user),
            ]
        );

        return $summary;
    }

    /**
     * Determine funnel stage
     */
    private function determineFunnelStage(User $user): string
    {
        if ($user->escrows()->where('status', 'completed')->exists()) {
            return 'buyer';
        }
        if ($user->escrows()->exists()) {
            return 'inquirer';
        }
        if (UserBehaviorLog::where('user_id', $user->id)->where('event_type', 'contact_agent')->exists()) {
            return 'inquirer';
        }
        if (UserBehaviorLog::where('user_id', $user->id)->where('event_type', 'search')->exists()) {
            return 'searcher';
        }
        if (UserBehaviorLog::where('user_id', $user->id)->exists()) {
            return 'browser';
        }
        return 'visitor';
    }

    // Device detection helpers
    private function getDeviceType(): string
    {
        $agent = request()->userAgent();
        if (str_contains($agent, 'Mobile')) return 'mobile';
        if (str_contains($agent, 'Tablet')) return 'tablet';
        return 'desktop';
    }

    private function getBrowser(): string
    {
        $agent = request()->userAgent();
        if (str_contains($agent, 'Chrome')) return 'Chrome';
        if (str_contains($agent, 'Firefox')) return 'Firefox';
        if (str_contains($agent, 'Safari')) return 'Safari';
        return 'Other';
    }

    private function getOs(): string
    {
        $agent = request()->userAgent();
        if (str_contains($agent, 'Windows')) return 'Windows';
        if (str_contains($agent, 'Mac')) return 'macOS';
        if (str_contains($agent, 'Linux')) return 'Linux';
        if (str_contains($agent, 'Android')) return 'Android';
        if (str_contains($agent, 'iOS')) return 'iOS';
        return 'Other';
    }
}