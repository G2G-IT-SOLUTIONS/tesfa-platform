<?php

namespace App\Services\AI;

use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates multi-service AI workflows
 */
class AIPipelineService
{
    public function __construct(
        private AVMService $avmService,
        private FraudDetectionService $fraudService,
        private CreditScoringService $creditService,
        private DemandForecastService $demandService,
        private DynamicPricingService $pricingService,
        private UserBehaviorService $behaviorService,
        private ImageQualityService $imageService,
        private DocumentOCRService $ocrService,
        private SiteHealthService $healthService,
    ) {}

    /**
     * Full property analysis pipeline
     */
    public function analyzeProperty(Property $property): array
    {
        Log::info('Starting property analysis pipeline', ['property_id' => $property->id]);

        $results = [
            'property_id' => $property->id,
            'analyzed_at' => now()->toIso8601String(),
        ];

        // 1. Fraud detection scan
        try {
            $fraudAlert = $this->fraudService->scanProperty($property);
            $results['fraud'] = [
                'detected' => $fraudAlert !== null,
                'alert_id' => $fraudAlert?->id,
                'severity' => $fraudAlert?->severity,
                'confidence' => $fraudAlert?->confidence_score,
            ];
        } catch (\Exception $e) {
            Log::error('Fraud scan failed', ['error' => $e->getMessage()]);
            $results['fraud'] = ['error' => $e->getMessage()];
        }

        // 2. AVM valuation
        try {
            $valuation = $this->avmService->valuate($property);
            $results['valuation'] = [
                'estimated_value' => $valuation->estimated_value,
                'confidence' => $valuation->confidence_score,
                'range_low' => $valuation->value_range_low,
                'range_high' => $valuation->value_range_high,
            ];
        } catch (\Exception $e) {
            Log::error('AVM valuation failed', ['error' => $e->getMessage()]);
            $results['valuation'] = ['error' => $e->getMessage()];
        }

        // 3. Image quality assessment
        try {
            $photos = $property->getMedia('photos');
            $imageResults = [];
            foreach ($photos as $photo) {
                $assessment = $this->imageService->assess($property->id, $photo->getPathRelativeToRoot());
                $imageResults[] = [
                    'path' => $photo->getUrl(),
                    'score' => $assessment->overall_score,
                    'status' => $assessment->status,
                ];
            }
            $results['images'] = $imageResults;
        } catch (\Exception $e) {
            Log::error('Image quality assessment failed', ['error' => $e->getMessage()]);
            $results['images'] = ['error' => $e->getMessage()];
        }

        // 4. Dynamic pricing recommendation (if short-term)
        if ($property->short_term_enabled) {
            try {
                $recommendation = $this->pricingService->recommendPriceChange($property);
                $results['pricing'] = [
                    'current_rate' => $recommendation->current_rate,
                    'recommended_rate' => $recommendation->recommended_rate,
                    'change_pct' => $recommendation->rate_change_pct,
                    'event_name' => $recommendation->event_name,
                ];
            } catch (\Exception $e) {
                Log::error('Pricing recommendation failed', ['error' => $e->getMessage()]);
                $results['pricing'] = ['error' => $e->getMessage()];
            }
        }

        Log::info('Property analysis pipeline complete', $results);

        return $results;
    }

    /**
     * Full user analysis pipeline
     */
    public function analyzeUser(User $user): array
    {
        Log::info('Starting user analysis pipeline', ['user_id' => $user->id]);

        $results = [
            'user_id' => $user->id,
            'analyzed_at' => now()->toIso8601String(),
        ];

        // 1. Credit score
        try {
            $creditScore = $this->creditService->computeScore($user);
            $results['credit_score'] = [
                'score' => $creditScore->score,
                'tier' => $creditScore->score_tier,
                'default_probability' => $this->creditService->predictDefaultProbability($user),
            ];
        } catch (\Exception $e) {
            Log::error('Credit score computation failed', ['error' => $e->getMessage()]);
            $results['credit_score'] = ['error' => $e->getMessage()];
        }

        // 2. Behavior analysis
        try {
            $results['behavior'] = [
                'engagement_score' => $this->behaviorService->computeEngagementScore($user),
                'intent_score' => $this->behaviorService->computeIntentScore($user),
                'churn_risk' => $this->behaviorService->computeChurnRisk($user),
            ];
        } catch (\Exception $e) {
            Log::error('Behavior analysis failed', ['error' => $e->getMessage()]);
            $results['behavior'] = ['error' => $e->getMessage()];
        }

        Log::info('User analysis pipeline complete', $results);

        return $results;
    }

    /**
     * Nightly batch pipeline
     */
    public function runNightlyBatch(): array
    {
        Log::info('Starting nightly batch pipeline');

        $results = [
            'started_at' => now()->toIso8601String(),
            'steps' => [],
        ];

        // 1. Update demand forecasts
        try {
            $this->demandService->generateAllForecasts();
            $this->demandService->validateForecasts();
            $results['steps']['demand_forecast'] = 'success';
        } catch (\Exception $e) {
            $results['steps']['demand_forecast'] = 'failed: ' . $e->getMessage();
        }

        // 2. Update credit scores
        try {
            User::where('is_active', true)->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $this->creditService->computeScore($user);
                }
            });
            $results['steps']['credit_scores'] = 'success';
        } catch (\Exception $e) {
            $results['steps']['credit_scores'] = 'failed: ' . $e->getMessage();
        }

        // 3. Update behavior summaries
        try {
            User::where('is_active', true)->chunk(100, function ($users) {
                foreach ($users as $user) {
                    $this->behaviorService->updateDailySummary($user);
                }
            });
            $results['steps']['behavior_summaries'] = 'success';
        } catch (\Exception $e) {
            $results['steps']['behavior_summaries'] = 'failed: ' . $e->getMessage();
        }

        // 4. Site health check
        try {
            $this->healthService->checkAllMetrics();
            $results['steps']['site_health'] = 'success';
        } catch (\Exception $e) {
            $results['steps']['site_health'] = 'failed: ' . $e->getMessage();
        }

        $results['completed_at'] = now()->toIso8601String();

        Log::info('Nightly batch pipeline complete', $results);

        return $results;
    }
}