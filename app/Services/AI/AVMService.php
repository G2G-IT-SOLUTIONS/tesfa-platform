<?php

namespace App\Services\AI;

use App\Models\Property;
use App\Models\AVMValuation;
use App\Models\MarketMetric;
use Phpml\Regression\LeastSquares;
use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AVMService
{
    private const MODEL_VERSION = 'avm_v1.0';
    private const CACHE_TTL = 3600; // 1 hour
    private const COMPARABLE_RADIUS_KM = 2.0;

    /**
     * Generate valuation for a property
     */
    public function valuate(Property $property): AVMValuation
    {
        $cacheKey = "avm:{$property->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($property) {
            // Gather features
            $features = $this->extractFeatures($property);

            // Get comparable properties
            $comparables = $this->findComparables($property);

            // Get market conditions
            $marketConditions = $this->getMarketConditions($property->neighborhood);

            // Calculate estimated value
            $estimate = $this->calculateEstimate($features, $comparables, $marketConditions);

            // Calculate confidence
            $confidence = $this->calculateConfidence($comparables, $features);

            // Calculate value range (±10-15% based on confidence)
            $rangePct = $confidence > 0.8 ? 0.10 : ($confidence > 0.6 ? 0.12 : 0.15);
            $low = $estimate * (1 - $rangePct);
            $high = $estimate * (1 + $rangePct);

            // Create valuation record
            $valuation = AVMValuation::create([
                'property_id' => $property->id,
                'estimated_value' => round($estimate, 2),
                'confidence_score' => round($confidence, 4),
                'value_range_low' => round($low, 2),
                'value_range_high' => round($high, 2),
                'model_version' => self::MODEL_VERSION,
                'features_used' => $features,
                'feature_importance' => $this->getFeatureImportance(),
                'comparable_properties' => collect($comparables)->pluck('id')->toArray(),
                'market_conditions' => $marketConditions,
                'validation_status' => 'pending',
                'computed_at' => now(),
            ]);

            Log::info('AVM valuation generated', [
                'property_id' => $property->id,
                'estimated_value' => $estimate,
                'confidence' => $confidence,
            ]);

            return $valuation;
        });
    }

    /**
     * Extract features from property
     */
    private function extractFeatures(Property $property): array
    {
        return [
            'location_lat' => $property->location->getLat(),
            'location_lng' => $property->location->getLng(),
            'area_sqm' => $property->area_sqm ?? 100,
            'bedrooms' => $property->bedrooms ?? 2,
            'bathrooms' => $property->bathrooms ?? 1,
            'condition' => $this->conditionToNumeric($property->condition),
            'neighborhood_avg_price' => $this->getNeighborhoodAvgPrice($property->neighborhood),
            'recent_sales_count' => $this->getRecentSalesCount($property->neighborhood),
            'days_on_market' => $property->created_at->diffInDays(now()),
            'seasonality_factor' => $this->getSeasonalityFactor(),
            'floor_number' => $property->floor_number ?? 1,
            'year_built' => $property->year_built ?? 2015,
        ];
    }

    /**
     * Find comparable properties within radius
     */
    private function findComparables(Property $property): array
    {
        $lat = $property->location->getLat();
        $lng = $property->location->getLng();

        // Using MySQL spatial functions
        $comparables = Property::query()
            ->where('id', '!=', $property->id)
            ->where('type', $property->type)
            ->where('purpose', $property->purpose)
            ->where('status', 'sold')
            ->whereRaw("ST_Distance_Sphere(location, POINT(?, ?)) <= ?", [
                $lat, $lng, self::COMPARABLE_RADIUS_KM * 1000
            ])
            ->whereBetween('area_sqm', [
                ($property->area_sqm ?? 100) * 0.8,
                ($property->area_sqm ?? 100) * 1.2
            ])
            ->orderByRaw("ST_Distance_Sphere(location, POINT(?, ?))", [$lat, $lng])
            ->limit(10)
            ->get();

        return $comparables->toArray();
    }

    /**
     * Get market conditions for neighborhood
     */
    private function getMarketConditions(string $neighborhood): array
    {
        $latest = MarketMetric::where('neighborhood', $neighborhood)
            ->latest('metric_date')
            ->first();

        return [
            'avg_price_per_sqm' => $latest?->avg_price_per_sqm ?? 50000,
            'inventory_level' => $latest?->active_listings ?? 50,
            'demand_score' => $latest?->demand_score ?? 50,
            'price_trend_30d' => $latest?->price_change_30d ?? 0,
            'avg_days_on_market' => $latest?->avg_days_on_market ?? 45,
        ];
    }

    /**
     * Calculate estimate using regression
     */
    private function calculateEstimate(array $features, array $comparables, array $market): float
    {
        // Base estimate from comparable properties
        if (count($comparables) > 0) {
            $compPrices = collect($comparables)->pluck('price');
            $baseEstimate = $compPrices->avg();
        } else {
            // Fallback: use neighborhood average price per sqm
            $baseEstimate = ($features['area_sqm'] ?? 100) * ($market['avg_price_per_sqm'] ?? 50000);
        }

        // Apply adjustments
        $adjustments = [
            'condition' => ($features['condition'] - 3) * 0.05, // ±10% based on condition
            'floor' => ($features['floor_number'] - 1) * 0.02, // 2% per floor
            'seasonality' => ($features['seasonality_factor'] - 1) * 0.03,
            'demand' => ($market['demand_score'] - 50) / 500, // ±10% based on demand
            'trend' => $market['price_trend_30d'] / 100,
        ];

        $totalAdjustment = array_sum($adjustments);
        $estimate = $baseEstimate * (1 + $totalAdjustment);

        return $estimate;
    }

    /**
     * Calculate confidence score
     */
    private function calculateConfidence(array $comparables, array $features): float
    {
        $confidence = 0.5; // Base confidence

        // More comparables = higher confidence
        $compCount = count($comparables);
        $confidence += min($compCount * 0.05, 0.3);

        // Complete data = higher confidence
        $requiredFields = ['area_sqm', 'bedrooms', 'bathrooms', 'condition'];
        $filledFields = 0;
        foreach ($requiredFields as $field) {
            if (!empty($features[$field])) {
                $filledFields++;
            }
        }
        $confidence += ($filledFields / count($requiredFields)) * 0.2;

        return min($confidence, 0.95);
    }

    /**
     * Get feature importance weights
     */
    private function getFeatureImportance(): array
    {
        return [
            'location_lat' => 0.25,
            'location_lng' => 0.25,
            'area_sqm' => 0.20,
            'bedrooms' => 0.10,
            'bathrooms' => 0.05,
            'condition' => 0.08,
            'neighborhood_avg_price' => 0.04,
            'recent_sales_count' => 0.01,
            'days_on_market' => 0.01,
            'seasonality_factor' => 0.01,
        ];
    }

    /**
     * Convert condition to numeric score
     */
    private function conditionToNumeric(?string $condition): int
    {
        return match($condition) {
            'new' => 5,
            'excellent' => 4,
            'good' => 3,
            'fair' => 2,
            'needs_renovation' => 1,
            default => 3,
        };
    }

    /**
     * Get neighborhood average price
     */
    private function getNeighborhoodAvgPrice(string $neighborhood): float
    {
        return Property::where('neighborhood', $neighborhood)
            ->where('status', 'active')
            ->avg('price_per_sqm') ?? 50000;
    }

    /**
     * Get recent sales count in neighborhood
     */
    private function getRecentSalesCount(string $neighborhood): int
    {
        return Property::where('neighborhood', $neighborhood)
            ->where('status', 'sold')
            ->where('sold_at', '>=', now()->subDays(90))
            ->count();
    }

    /**
     * Get seasonality factor (1.0 = neutral)
     */
    private function getSeasonalityFactor(): float
    {
        $month = now()->month;

        // Peak season: Sep-Jan (dry season, holidays)
        // Low season: Jun-Aug (rainy season)
        return match(true) {
            in_array($month, [9, 10, 11, 12, 1]) => 1.15,
            in_array($month, [2, 3, 4, 5]) => 1.0,
            in_array($month, [6, 7, 8]) => 0.85,
            default => 1.0,
        };
    }

    /**
     * Validate prediction with actual sale price
     */
    public function validateWithSale(AVMValuation $valuation, float $actualPrice): void
    {
        $accuracy = 1 - (abs($valuation->estimated_value - $actualPrice) / $actualPrice);

        $valuation->update([
            'actual_sale_price' => $actualPrice,
            'accuracy_pct' => round($accuracy * 100, 2),
            'validation_status' => 'validated',
            'validated_at' => now(),
        ]);

        Log::info('AVM validated', [
            'valuation_id' => $valuation->id,
            'estimated' => $valuation->estimated_value,
            'actual' => $actualPrice,
            'accuracy' => $accuracy,
        ]);
    }

    /**
     * Generate accuracy report
     */
    public function getAccuracyReport(string $neighborhood = null): array
    {
        $query = AVMValuation::where('validation_status', 'validated')
            ->whereNotNull('accuracy_pct');

        if ($neighborhood) {
            $query->whereHas('property', fn ($q) => $q->where('neighborhood', $neighborhood));
        }

        $validated = $query->get();

        return [
            'total_validated' => $validated->count(),
            'avg_accuracy' => round($validated->avg('accuracy_pct'), 2),
            'median_accuracy' => round($validated->median('accuracy_pct'), 2),
            'within_10_pct' => $validated->where('accuracy_pct', '>=', 90)->count(),
            'within_15_pct' => $validated->where('accuracy_pct', '>=', 85)->count(),
            'outliers' => $validated->where('accuracy_pct', '<', 70)->count(),
        ];
    }
}