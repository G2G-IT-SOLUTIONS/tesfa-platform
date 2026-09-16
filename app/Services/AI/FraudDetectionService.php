<?php

namespace App\Services\AI;

use App\Models\Property;
use App\Models\User;
use App\Models\FraudAlert;
use App\Models\FraudPattern;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FraudDetectionService
{
    private const MODEL_VERSION = 'fraud_v1.0';
    private const AUTO_APPROVE_THRESHOLD = 0.3;
    private const REVIEW_THRESHOLD = 0.7;
    private const BLOCK_THRESHOLD = 0.9;

    /**
     * Main entry point: Scan property for fraud
     */
    public function scanProperty(Property $property): ?FraudAlert
    {
        $results = [
            'duplicate' => $this->checkDuplicateListing($property),
            'price_anomaly' => $this->checkPriceAnomaly($property),
            'seller_velocity' => $this->checkSellerVelocity($property),
            'new_account_burst' => $this->checkNewAccountBurst($property),
            'document_inconsistency' => $this->checkDocumentInconsistency($property),
            'agent_self_verification' => $this->checkAgentSelfVerification($property),
            'suspicious_contact' => $this->checkSuspiciousContactPatterns($property),
            'image_theft' => $this->checkImageTheft($property),
            'geolocation_mismatch' => $this->checkGeolocationMismatch($property),
            'payment_pattern' => $this->checkPaymentPattern($property),
        ];

        // Calculate composite score
        $triggeredRules = [];
        $totalScore = 0;
        $ruleCount = 0;

        foreach ($results as $rule => $result) {
            if ($result['triggered']) {
                $triggeredRules[] = [
                    'rule' => $rule,
                    'confidence' => $result['confidence'],
                    'details' => $result['details'],
                ];
                $totalScore += $result['confidence'];
                $ruleCount++;
            }
        }

        $riskScore = $ruleCount > 0 ? min($totalScore / max($ruleCount, 1), 1.0) : 0;

        // Determine risk level
        $riskLevel = match(true) {
            $riskScore < self::AUTO_APPROVE_THRESHOLD => 'low',
            $riskScore < 0.6 => 'medium',
            $riskScore < 0.8 => 'high',
            default => 'critical',
        };

        $scanResults = [
            'risk_score' => round($riskScore, 4),
            'risk_level' => $riskLevel,
            'triggered_rules' => $triggeredRules,
            'recommendation' => match(true) {
                $riskScore < self::AUTO_APPROVE_THRESHOLD => 'approve',
                $riskScore < 0.6 => 'review',
                $riskScore < 0.8 => 'flag_for_admin',
                default => 'auto_block',
            },
        ];

        // Create alert if needed
        if ($riskLevel !== 'low') {
            return $this->createAlert($property, $scanResults);
        }

        return null;
    }

    /**
     * Check for duplicate listings (same GPS + similar area)
     */
    private function checkDuplicateListing(Property $property): array
    {
        $lat = $property->location->getLat();
        $lng = $property->location->getLng();

        $duplicates = Property::query()
            ->where('id', '!=', $property->id)
            ->where('status', 'active')
            ->whereRaw("ST_Distance_Sphere(location, POINT(?, ?)) <= ?", [$lat, $lng, 50])
            ->whereBetween('area_sqm', [
                ($property->area_sqm ?? 100) * 0.9,
                ($property->area_sqm ?? 100) * 1.1
            ])
            ->get();

        $triggered = $duplicates->isNotEmpty();

        return [
            'triggered' => $triggered,
            'confidence' => $triggered ? 0.85 : 0,
            'details' => [
                'duplicate_count' => $duplicates->count(),
                'duplicate_ids' => $duplicates->pluck('id')->toArray(),
                'distance_threshold_m' => 50,
            ],
        ];
    }

    /**
     * Check for price anomalies (deviation from neighborhood average)
     */
    private function checkPriceAnomaly(Property $property): array
    {
        $neighborhoodAvg = Property::where('neighborhood', $property->neighborhood)
            ->where('status', 'active')
            ->where('type', $property->type)
            ->avg('price_per_sqm');

        if (!$neighborhoodAvg || $neighborhoodAvg == 0) {
            return ['triggered' => false, 'confidence' => 0, 'details' => []];
        }

        $deviation = abs($property->price_per_sqm - $neighborhoodAvg) / $neighborhoodAvg;
        $isAnomaly = $deviation > 2.0; // More than 2x average

        return [
            'triggered' => $isAnomaly,
            'confidence' => min($deviation / 3, 1.0),
            'details' => [
                'property_price_per_sqm' => $property->price_per_sqm,
                'neighborhood_avg' => round($neighborhoodAvg, 2),
                'deviation_factor' => round($deviation, 2),
                'direction' => $property->price_per_sqm > $neighborhoodAvg ? 'above_average' : 'below_average',
            ],
        ];
    }

    /**
     * Check seller velocity (too many listings in short time)
     */
    private function checkSellerVelocity(Property $property): array
    {
        $seller = $property->owner;
        $recentListings = Property::where('owner_id', $seller->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $accountAgeDays = $seller->created_at->diffInDays(now());

        $isSuspicious = ($accountAgeDays < 30 && $recentListings > 3) || $recentListings > 10;

        return [
            'triggered' => $isSuspicious,
            'confidence' => $accountAgeDays < 7 ? 0.95 : 0.7,
            'details' => [
                'account_age_days' => $accountAgeDays,
                'recent_listings_7d' => $recentListings,
                'total_listings' => Property::where('owner_id', $seller->id)->count(),
            ],
        ];
    }

    /**
     * Check new account burst (account created recently + already listing)
     */
    private function checkNewAccountBurst(Property $property): array
    {
        $seller = $property->owner;
        $accountAgeHours = $seller->created_at->diffInHours(now());

        if ($accountAgeHours > 24) {
            return ['triggered' => false, 'confidence' => 0, 'details' => []];
        }

        $listingsCount = Property::where('owner_id', $seller->id)->count();

        return [
            'triggered' => $listingsCount > 0,
            'confidence' => $seller->id_verified ? 0.5 : 0.95,
            'details' => [
                'account_age_hours' => $accountAgeHours,
                'listings_count' => $listingsCount,
                'verification_status' => $seller->id_verified ? 'verified' : 'unverified',
            ],
        ];
    }

    /**
     * Check document inconsistencies
     */
    private function checkDocumentInconsistency(Property $property): array
    {
        $issues = [];

        $docs = $property->documents;

        foreach ($docs as $doc) {
            if ($doc->issue_date && $doc->issue_date->isFuture()) {
                $issues[] = ['document' => $doc->type, 'issue' => 'future_date'];
            }
        }

        // Check if lease certificate predates building permit
        $lease = $docs->where('type', 'lease_certificate')->first();
        $permit = $docs->where('type', 'building_permit')->first();

        if ($lease && $permit && $lease->issue_date < $permit->issue_date) {
            $issues[] = ['issue' => 'lease_before_permit'];
        }

        return [
            'triggered' => !empty($issues),
            'confidence' => 0.9,
            'details' => ['document_issues' => $issues],
        ];
    }

    /**
     * Check agent self-verification (agent verifying own listing)
     */
    private function checkAgentSelfVerification(Property $property): array
    {
        if (!$property->agent_id) {
            return ['triggered' => false, 'confidence' => 0, 'details' => []];
        }

        $verification = $property->verifications()
            ->where('agent_id', $property->agent_id)
            ->first();

        $triggered = $verification !== null;

        return [
            'triggered' => $triggered,
            'confidence' => 1.0, // Always block this
            'details' => [
                'agent_id' => $property->agent_id,
                'is_self_verified' => $triggered,
            ],
        ];
    }

    /**
     * Check suspicious contact patterns (off-platform communication)
     */
    private function checkSuspiciousContactPatterns(Property $property): array
    {
        $seller = $property->owner;

        $offPlatformAttempts = $seller->messages()
            ->where('content', 'REGEXP', '(whatsapp|telegram|email|phone|call me|contact me directly)')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        return [
            'triggered' => $offPlatformAttempts > 2,
            'confidence' => min($offPlatformAttempts * 0.2, 0.9),
            'details' => ['off_platform_attempts' => $offPlatformAttempts],
        ];
    }

    /**
     * Check image theft using AWS Rekognition
     */
    private function checkImageTheft(Property $property): array
    {
        if (!config('services.aws.rekognition_enabled')) {
            return ['triggered' => false, 'confidence' => 0, 'details' => ['note' => 'AWS Rekognition disabled']];
        }

        // TODO: Implement AWS Rekognition reverse image search
        // This requires storing image hashes and searching against them

        return [
            'triggered' => false,
            'confidence' => 0,
            'details' => ['note' => 'Requires AWS Rekognition integration'],
        ];
    }

    /**
     * Check geolocation mismatch
     */
    private function checkGeolocationMismatch(Property $property): array
    {
        // TODO: Reverse geocode the address and compare with GPS coordinates
        return [
            'triggered' => false,
            'confidence' => 0,
            'details' => ['note' => 'Requires geocoding verification'],
        ];
    }

    /**
     * Check payment patterns
     */
    private function checkPaymentPattern(Property $property): array
    {
        $seller = $property->owner;

        $chargebacks = $seller->escrows()
            ->where('status', 'refunded')
            ->where('refunded_at', '>=', now()->subDays(90))
            ->count();

        $disputes = $seller->escrows()
            ->where('status', 'disputed')
            ->count();

        $triggered = $chargebacks > 1 || $disputes > 2;

        return [
            'triggered' => $triggered,
            'confidence' => min(($chargebacks * 0.3) + ($disputes * 0.15), 1.0),
            'details' => [
                'recent_chargebacks' => $chargebacks,
                'total_disputes' => $disputes,
            ],
        ];
    }

    /**
     * Create fraud alert from scan results
     */
    private function createAlert(Property $property, array $scanResults): FraudAlert
    {
        $severity = match($scanResults['risk_level']) {
            'medium' => 'medium',
            'high' => 'high',
            'critical' => 'critical',
            default => 'low',
        };

        $alert = FraudAlert::create([
            'alert_type' => $this->determineAlertType($scanResults['triggered_rules']),
            'severity' => $severity,
            'target_type' => 'property',
            'target_id' => $property->id,
            'target_type_id' => "property:{$property->id}",
            'detection_method' => 'rule_based',
            'confidence_score' => $scanResults['risk_score'],
            'evidence' => [
                'rules_triggered' => $scanResults['triggered_rules'],
                'risk_score' => $scanResults['risk_score'],
                'property_id' => $property->id,
                'seller_id' => $property->owner_id,
            ],
            'status' => 'new',
            'admin_notified' => $severity === 'critical',
        ]);

        // Auto-actions for critical
        if ($severity === 'critical') {
            $property->update(['status' => 'paused']);
            // TODO: Notify admin via SMS/PagerDuty
        }

        Log::warning('Fraud alert created', [
            'alert_id' => $alert->id,
            'property_id' => $property->id,
            'severity' => $severity,
            'risk_score' => $scanResults['risk_score'],
        ]);

        return $alert;
    }

    /**
     * Determine primary alert type from triggered rules
     */
    private function determineAlertType(array $triggeredRules): string
    {
        $ruleNames = array_column($triggeredRules, 'rule');

        if (in_array('duplicate', $ruleNames)) return 'duplicate_listing';
        if (in_array('price_anomaly', $ruleNames)) return 'price_anomaly';
        if (in_array('agent_self_verification', $ruleNames)) return 'agent_collusion';
        if (in_array('document_inconsistency', $ruleNames)) return 'fake_document';
        if (in_array('new_account_burst', $ruleNames)) return 'velocity_abuse';

        return 'suspicious_payment';
    }

    /**
     * Process admin feedback for model improvement
     */
    public function processFeedback(FraudAlert $alert, bool $isConfirmedFraud): void
    {
        $alert->update([
            'status' => $isConfirmedFraud ? 'confirmed_fraud' : 'false_positive',
            'resolved_at' => now(),
            'feedback_loop_status' => 'pending',
        ]);

        // Update pattern accuracy
        $pattern = FraudPattern::where('pattern_name', $alert->alert_type)->first();
        if ($pattern) {
            $pattern->increment('hit_count');
            if (!$isConfirmedFraud) {
                $pattern->increment('false_positive_count');
            }
        }

        // TODO: Queue model retraining if enough feedback accumulated
    }
}