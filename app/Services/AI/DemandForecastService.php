<?php

namespace App\Services\AI;

use App\Models\DemandForecast;
use App\Models\DemandEvent;
use App\Models\Property;
use App\Models\UserBehaviorLog;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;

class DemandForecastService
{
    private const MODEL_VERSION = 'demand_v1.0';
    private const FORECAST_DAYS = 30;
    private const TRAINING_DAYS = 90;

    /**
     * Generate forecasts for all neighborhoods
     */
    public function generateAllForecasts(): array
    {
        $neighborhoods = Property::distinct('neighborhood')->pluck('neighborhood');
        $propertyTypes = ['apartment', 'villa', 'townhouse', 'land', 'commercial'];

        $results = [];

        foreach ($neighborhoods as $neighborhood) {
            foreach ($propertyTypes as $type) {
                $results[] = $this->generateForecast($neighborhood, $type);
            }
        }

        Log::info('Demand forecasts generated', ['count' => count($results)]);

        return $results;
    }

    /**
     * Generate forecast for a specific neighborhood/type
     */
    public function generateForecast(string $neighborhood, string $propertyType): array
    {
        // Get historical data
        $historicalData = $this->getHistoricalData($neighborhood, $propertyType);

        if (count($historicalData) < 30) {
            // Not enough data, use simple average
            return $this->generateSimpleForecast($neighborhood, $propertyType, $historicalData);
        }

        // Use Prophet via Python
        $forecasts = $this->runProphet($historicalData);

        // Store forecasts
        $stored = [];
        foreach ($forecasts as $forecast) {
            $stored[] = DemandForecast::updateOrCreate(
                [
                    'neighborhood' => $neighborhood,
                    'property_type' => $propertyType,
                    'prediction_for_date' => $forecast['date'],
                    'model_version' => self::MODEL_VERSION,
                ],
                [
                    'forecast_date' => today(),
                    'days_ahead' => now()->diffInDays($forecast['date']),
                    'demand_index' => $forecast['value'],
                    'demand_index_low' => $forecast['lower'],
                    'demand_index_high' => $forecast['upper'],
                    'features_used' => [
                        'historical_avg' => array_sum(array_column($historicalData, 'value')) / count($historicalData),
                        'seasonal_factor' => $this->getSeasonalFactor($forecast['date']),
                        'event_impact' => $this->getEventImpact($neighborhood, $forecast['date']),
                    ],
                    'model_type' => 'prophet',
                    'training_data_start' => now()->subDays(self::TRAINING_DAYS)->toDateString(),
                    'training_data_end' => today()->toDateString(),
                    'training_samples' => count($historicalData),
                    'expires_at' => now()->addDays(self::FORECAST_DAYS),
                ]
            );
        }

        return [
            'neighborhood' => $neighborhood,
            'property_type' => $propertyType,
            'forecasts_count' => count($stored),
        ];
    }

    /**
     * Get historical data for training
     */
    private function getHistoricalData(string $neighborhood, string $propertyType): array
    {
        $data = [];

        for ($i = self::TRAINING_DAYS; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();

            // Count searches for this neighborhood
            $searches = UserBehaviorLog::where('event_type', 'search')
                ->where('property_neighborhood', $neighborhood)
                ->whereDate('created_at', $date)
                ->count();

            // Count inquiries
            $inquiries = UserBehaviorLog::where('event_type', 'contact_agent')
                ->where('property_neighborhood', $neighborhood)
                ->whereDate('created_at', $date)
                ->count();

            // Count views
            $views = UserBehaviorLog::where('event_type', 'page_view')
                ->where('property_neighborhood', $neighborhood)
                ->whereDate('created_at', $date)
                ->count();

            // Calculate demand index (0-100)
            $demandIndex = min(100, ($searches * 3) + ($inquiries * 5) + ($views * 0.5));

            $data[] = [
                'date' => $date,
                'value' => round($demandIndex, 2),
                'searches' => $searches,
                'inquiries' => $inquiries,
                'views' => $views,
            ];
        }

        return $data;
    }

    /**
     * Run Prophet forecasting via Python
     */
    private function runProphet(array $historicalData): array
    {
        // Prepare data for Python
        $inputData = array_map(fn ($d) => [
            'ds' => $d['date'],
            'y' => $d['value'],
        ], $historicalData);

        // Write to temp file
        $inputFile = storage_path('app/prophet_input.json');
        $outputFile = storage_path('app/prophet_output.json');

        file_put_contents($inputFile, json_encode($inputData));

        // Run Python script
        $script = base_path('scripts/prophet_forecast.py');
        $result = Process::run("python3 {$script} {$inputFile} {$outputFile} " . self::FORECAST_DAYS);

        if (!$result->successful()) {
            Log::error('Prophet forecast failed', ['error' => $result->errorOutput()]);
            return $this->generateFallbackForecast($historicalData);
        }

        $forecasts = json_decode(file_get_contents($outputFile), true);

        return $forecasts;
    }

    /**
     * Python Prophet script
     */
    private function generateProphetScript(): string
    {
        return <<<'PYTHON'
import sys
import json
import pandas as pd
from prophet import Prophet

input_file = sys.argv[1]
output_file = sys.argv[2]
forecast_days = int(sys.argv[3])

# Load data
with open(input_file) as f:
    data = json.load(f)

df = pd.DataFrame(data)
df['ds'] = pd.to_datetime(df['ds'])

# Initialize Prophet
model = Prophet(
    daily_seasonality=False,
    weekly_seasonality=True,
    yearly_seasonality=True,
    changepoint_prior_scale=0.05,
)

# Fit model
model.fit(df)

# Create future dataframe
future = model.make_future_dataframe(periods=forecast_days)

# Forecast
forecast = model.predict(future)

# Prepare output
output = []
for _, row in forecast.tail(forecast_days).iterrows():
    output.append({
        'date': row['ds'].strftime('%Y-%m-%d'),
        'value': max(0, min(100, round(row['yhat'], 2))),
        'lower': max(0, round(row['yhat_lower'], 2)),
        'upper': min(100, round(row['yhat_upper'], 2)),
    })

with open(output_file, 'w') as f:
    json.dump(output, f)

print(f"Generated {len(output)} forecasts")
PYTHON;
    }

    /**
     * Generate fallback forecast when Prophet unavailable
     */
    private function generateFallbackForecast(array $historicalData): array
    {
        $avg = array_sum(array_column($historicalData, 'value')) / max(count($historicalData), 1);
        $forecasts = [];

        for ($i = 0; $i < self::FORECAST_DAYS; $i++) {
            $date = now()->addDays($i);
            $seasonalFactor = $this->getSeasonalFactor($date->toDateString());
            $value = $avg * $seasonalFactor;

            $forecasts[] = [
                'date' => $date->toDateString(),
                'value' => round(min(100, max(0, $value)), 2),
                'lower' => round(max(0, $value * 0.8), 2),
                'upper' => round(min(100, $value * 1.2), 2),
            ];
        }

        return $forecasts;
    }

    /**
     * Get seasonal factor for a date
     */
    private function getSeasonalFactor(string $date): float
    {
        $month = (int) date('n', strtotime($date));

        // Ethiopian seasons
        return match(true) {
            in_array($month, [9, 10, 11, 12, 1]) => 1.15, // Dry season
            in_array($month, [2, 3, 4, 5]) => 1.0, // Moderate
            in_array($month, [6, 7, 8]) => 0.85, // Rainy season
            default => 1.0,
        };
    }

    /**
     * Get event impact multiplier
     */
    private function getEventImpact(string $neighborhood, string $date): array
    {
        $events = DemandEvent::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->whereJsonContains('affected_neighborhoods', $neighborhood)
            ->get();

        if ($events->isEmpty()) {
            return ['event' => null, 'multiplier' => 1.0];
        }

        $event = $events->first();

        return [
            'event' => $event->event_name,
            'multiplier' => $event->expected_demand_multiplier,
        ];
    }

    /**
     * Validate forecasts with actuals
     */
    public function validateForecasts(): array
    {
        $forecasts = DemandForecast::where('prediction_for_date', '<=', today())
            ->where('is_validated', false)
            ->get();

        $validated = 0;
        $errors = [];

        foreach ($forecasts as $forecast) {
            $actual = $this->getActualDemand(
                $forecast->neighborhood,
                $forecast->prediction_for_date->toDateString()
            );

            if ($actual !== null) {
                $accuracy = 100 - abs(($forecast->demand_index - $actual) / max($actual, 1) * 100);

                $forecast->update([
                    'actual_value' => $actual,
                    'accuracy_pct' => round($accuracy, 2),
                    'is_validated' => true,
                    'status' => 'validated',
                ]);

                $validated++;
            }
        }

        return [
            'validated_count' => $validated,
            'average_accuracy' => DemandForecast::where('is_validated', true)->avg('accuracy_pct'),
        ];
    }

    /**
     * Get actual demand for validation
     */
    private function getActualDemand(string $neighborhood, string $date): ?float
    {
        $searches = UserBehaviorLog::where('event_type', 'search')
            ->where('property_neighborhood', $neighborhood)
            ->whereDate('created_at', $date)
            ->count();

        $inquiries = UserBehaviorLog::where('event_type', 'contact_agent')
            ->where('property_neighborhood', $neighborhood)
            ->whereDate('created_at', $date)
            ->count();

        $views = UserBehaviorLog::where('event_type', 'page_view')
            ->where('property_neighborhood', $neighborhood)
            ->whereDate('created_at', $date)
            ->count();

        if ($searches + $inquiries + $views === 0) {
            return null;
        }

        return min(100, ($searches * 3) + ($inquiries * 5) + ($views * 0.5));
    }
}