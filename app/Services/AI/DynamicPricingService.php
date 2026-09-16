<?php

namespace App\Services\AI;

use App\Models\Property;
use App\Models\DynamicPricingRecommendation;
use App\Models\DemandForecast;
use Carbon\Carbon;

class DynamicPricingService
{
    private const MODEL_VERSION = 'pricing_v1.0';
    private const MAX_SURGE = 2.0; // 200% max
    private const MIN_DISCOUNT = 0.7; // 30% max discount

    /**
     * Update all pricing recommendations
     */
    public function updateAllRecommendations(): array
    {
        $properties = Property::where('short_term_enabled', true)
            ->where('status', 'active')
            ->get();

        $results = [];

        foreach ($properties as $property) {
            $results[] = $this->recommendPriceChange($property);
        }

        return $results;
    }

    /**
     * Calculate optimal nightly rate
     */
    public function calculateOptimalNightlyRate(Property $property, Carbon $date): array
    {
        $baseRate = $property->nightly_rate ?? 3000;

        // Get multipliers
        $demandMultiplier = $this->getDemandMultiplier($property->neighborhood, $date);
        $eventMultiplier = $this->getEventMultiplier($property->neighborhood, $date);
        $competitorAdjustment = $this->getCompetitorAdjustment($property, $date);
        $dayOfWeekMultiplier = $this->getDayOfWeekMultiplier($date);
        $seasonalMultiplier = $this->getSeasonalMultiplier($date);

        // Calculate optimal rate
        $optimalRate = $baseRate
            * $demandMultiplier
            * $eventMultiplier
            * $competitorAdjustment
            * $dayOfWeekMultiplier
            * $seasonalMultiplier;

        // Apply bounds
        $maxRate = $baseRate * self::MAX_SURGE;
        $minRate = $baseRate * self::MIN_DISCOUNT;
        $optimalRate = max($minRate, min($maxRate, $optimalRate));

        return [
            'base_rate' => $baseRate,
            'optimal_rate' => round($optimalRate, 2),
            'demand_multiplier' => round($demandMultiplier, 2),
            'event_multiplier' => round($eventMultiplier, 2),
            'competitor_adjustment' => round($competitorAdjustment, 2),
            'day_of_week_multiplier' => round($dayOfWeekMultiplier, 2),
            'seasonal_multiplier' => round($seasonalMultiplier, 2),
        ];
    }

    /**
     * Generate price recommendation for property
     */
    public function recommendPriceChange(Property $property): DynamicPricingRecommendation
    {
        $currentRate = $property->nightly_rate ?? 3000;
        $date = now()->addDay();

        $pricing = $this->calculateOptimalNightlyRate($property, $date);

        // Get event info
        $event = $this->getUpcomingEvent($property->neighborhood, $date);

        // Calculate potential revenue increase
        $potentialIncrease = ($pricing['optimal_rate'] - $currentRate) * 30; // Assume 30 nights

        return DynamicPricingRecommendation::updateOrCreate(
            [
                'property_id' => $property->id,
                'pricing_date' => $date->toDateString(),
                'model_version' => self::MODEL_VERSION,
            ],
            [
                'current_rate' => $currentRate,
                'recommended_rate' => $pricing['optimal_rate'],
                'potential_revenue_increase' => $potentialIncrease,
                'base_rate' => $pricing['base_rate'],
                'demand_multiplier' => $pricing['demand_multiplier'],
                'event_multiplier' => $pricing['event_multiplier'],
                'competitor_adjustment' => $pricing['competitor_adjustment'],
                'day_of_week_multiplier' => $pricing['day_of_week_multiplier'],
                'seasonal_multiplier' => $pricing['seasonal_multiplier'],
                'days_until_event' => $event ? now()->diffInDays($event->start_date) : null,
                'event_name' => $event?->event_name,
                'competitor_rates_avg' => $this->getCompetitorRatesAvg($property, $date),
                'competitor_rates_count' => $this->getCompetitorRatesCount($property, $date),
                'host_action' => 'pending',
                'expires_at' => now()->addDays(7),
            ]
        );
    }

    /**
     * Get demand multiplier from forecasts
     */
    private function getDemandMultiplier(string $neighborhood, Carbon $date): float
    {
        $forecast = DemandForecast::where('neighborhood', $neighborhood)
            ->where('prediction_for_date', $date->toDateString())
            ->first();

        if (!$forecast) {
            return 1.0;
        }

        // Convert demand index (0-100) to multiplier (0.8-1.5)
        $demandIndex = $forecast->demand_index;

        return 0.8 + ($demandIndex / 100) * 0.7;
    }

    /**
     * Get event multiplier
     */
    private function getEventMultiplier(string $neighborhood, Carbon $date): float
    {
        $event = $this->getUpcomingEvent($neighborhood, $date);

        if (!$event) {
            return 1.0;
        }

        $daysUntil = now()->diffInDays($event->start_date);

        // Exponential surge as event approaches
        $multiplier = $event->expected_demand_multiplier;

        if ($daysUntil <= 7) {
            $multiplier *= 1.2;
        } elseif ($daysUntil <= 14) {
            $multiplier *= 1.1;
        }

        return min($multiplier, self::MAX_SURGE);
    }

    /**
     * Get upcoming event for neighborhood
     */
    private function getUpcomingEvent(string $neighborhood, Carbon $date): ?object
    {
        return \App\Models\DemandEvent::where('start_date', '<=', $date->copy()->addDays(30))
            ->where('end_date', '>=', $date)
            ->whereJsonContains('affected_neighborhoods', $neighborhood)
            ->orderBy('start_date')
            ->first();
    }

    /**
     * Get competitor rate adjustment
     */
    private function getCompetitorAdjustment(Property $property, Carbon $date): float
    {
        $competitorAvg = $this->getCompetitorRatesAvg($property, $date);

        if (!$competitorAvg || !$property->nightly_rate) {
            return 1.0;
        }

        // Adjust based on competitor pricing
        $ratio = $competitorAvg / $property->nightly_rate;

        // Limit adjustment to ±15%
        return max(0.85, min(1.15, $ratio));
    }

    /**
     * Get average competitor rates
     */
    private function getCompetitorRatesAvg(Property $property, Carbon $date): ?float
    {
        return Property::where('short_term_enabled', true)
            ->where('neighborhood', $property->neighborhood)
            ->where('id', '!=', $property->id)
            ->where('type', $property->type)
            ->avg('nightly_rate');
    }

    /**
     * Get competitor count
     */
    private function getCompetitorRatesCount(Property $property, Carbon $date): int
    {
        return Property::where('short_term_enabled', true)
            ->where('neighborhood', $property->neighborhood)
            ->where('id', '!=', $property->id)
            ->count();
    }

    /**
     * Get day of week multiplier
     */
    private function getDayOfWeekMultiplier(Carbon $date): float
    {
        // Friday, Saturday higher
        return match($date->dayOfWeek) {
            Carbon::FRIDAY, Carbon::SATURDAY => 1.15,
            Carbon::SUNDAY => 1.05,
            default => 1.0,
        };
    }

    /**
     * Get seasonal multiplier
     */
    private function getSeasonalMultiplier(Carbon $date): float
    {
        $month = $date->month;

        return match(true) {
            in_array($month, [9, 10, 11, 12, 1]) => 1.15, // Peak
            in_array($month, [2, 3, 4, 5]) => 1.0,
            in_array($month, [6, 7, 8]) => 0.85, // Low
            default => 1.0,
        };
    }

    /**
     * Apply recommendation
     */
    public function applyRecommendation(DynamicPricingRecommendation $recommendation): void
    {
        $recommendation->update([
            'host_action' => 'accepted',
            'host_action_at' => now(),
        ]);

        // Update property nightly rate
        $recommendation->property->update([
            'nightly_rate' => $recommendation->recommended_rate,
        ]);
    }

    /**
     * Apply bulk recommendations
     */
    public function applyBulkRecommendations(array $propertyIds = []): int
    {
        $query = DynamicPricingRecommendation::where('host_action', 'pending')
            ->where('expires_at', '>', now());

        if (!empty($propertyIds)) {
            $query->whereIn('property_id', $propertyIds);
        }

        $recommendations = $query->get();
        $applied = 0;

        foreach ($recommendations as $recommendation) {
            $this->applyRecommendation($recommendation);
            $applied++;
        }

        return $applied;
    }

    /**
     * Get revenue impact analysis
     */
    public function getRevenueImpact(Property $property): array
    {
        $recommendations = DynamicPricingRecommendation::where('property_id', $property->id)
            ->where('host_action', 'accepted')
            ->get();

        $totalPotentialIncrease = $recommendations->sum('potential_revenue_increase');
        $avgRateIncrease = $recommendations->avg('rate_change_pct');

        return [
            'total_recommendations' => $recommendations->count(),
            'total_potential_increase' => round($totalPotentialIncrease, 2),
            'avg_rate_increase_pct' => round($avgRateIncrease, 2),
            'accepted_count' => $recommendations->where('host_action', 'accepted')->count(),
        ];
    }
}