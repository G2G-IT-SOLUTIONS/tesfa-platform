<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\CreditScore;
use App\Models\CreditScoreLog;
use App\Models\RentToOwnPayment;
use Illuminate\Support\Facades\Log;

class CreditScoringService
{
    private const MODEL_VERSION = 'credit_v1.0';
    private const BASE_SCORE = 450;
    private const MIN_SCORE = 300;
    private const MAX_SCORE = 850;

    // Weight configuration
    private const WEIGHTS = [
        'payment_history' => 0.35,
        'amounts_owed' => 0.30,
        'credit_history_length' => 0.15,
        'credit_mix' => 0.10,
        'new_credit' => 0.10,
    ];

    /**
     * Compute credit score for a user
     */
    public function computeScore(User $user): CreditScore
    {
        $existingScore = CreditScore::where('user_id', $user->id)->first();
        $oldScore = $existingScore?->score ?? self::BASE_SCORE;

        // Calculate component scores
        $paymentHistory = $this->calculatePaymentHistoryScore($user);
        $amountsOwed = $this->calculateAmountsOwedScore($user);
        $creditLength = $this->calculateCreditLengthScore($user);
        $creditMix = $this->calculateCreditMixScore($user);
        $newCredit = $this->calculateNewCreditScore($user);

        // Weighted total
        $weightedScore = 
            ($paymentHistory * self::WEIGHTS['payment_history']) +
            ($amountsOwed * self::WEIGHTS['amounts_owed']) +
            ($creditLength * self::WEIGHTS['credit_history_length']) +
            ($creditMix * self::WEIGHTS['credit_mix']) +
            ($newCredit * self::WEIGHTS['new_credit']);

        // Convert to 300-850 scale
        $score = (int) round(self::MIN_SCORE + ($weightedScore / 100) * (self::MAX_SCORE - self::MIN_SCORE));
        $score = max(self::MIN_SCORE, min(self::MAX_SCORE, $score));

        // Determine tier
        $tier = $this->getTier($score);

        // Get detailed metrics
        $metrics = $this->getDetailedMetrics($user);

        // Create or update credit score
        $creditScore = CreditScore::updateOrCreate(
            ['user_id' => $user->id],
            [
                'score' => $score,
                'score_tier' => $tier,
                'payment_history_score' => $paymentHistory,
                'amounts_owed_score' => $amountsOwed,
                'credit_history_length_score' => $creditLength,
                'credit_mix_score' => $creditMix,
                'new_credit_score' => $newCredit,
                ...$metrics,
                'model_version' => self::MODEL_VERSION,
                'computed_at' => now(),
                'next_computation_at' => now()->addDays(7),
                'computation_trigger' => 'scheduled',
            ]
        );

        // Log score change
        if ($existingScore && $existingScore->score !== $score) {
            CreditScoreLog::create([
                'credit_score_id' => $creditScore->id,
                'user_id' => $user->id,
                'old_score' => $oldScore,
                'new_score' => $score,
                'score_change' => $score - $oldScore,
                'change_reason' => $this->determineChangeReason($user),
                'change_details' => ['previous_score' => $oldScore],
                'triggered_by' => 'system',
            ]);

            // Notify user of score change
            if ($score > $oldScore) {
                // TODO: Send notification
                Log::info("Credit score increased for user {$user->id}", [
                    'old' => $oldScore,
                    'new' => $score,
                ]);
            }
        }

        return $creditScore;
    }

    /**
     * Calculate payment history score (35% weight)
     */
    private function calculatePaymentHistoryScore(User $user): float
    {
        $payments = RentToOwnPayment::where('buyer_id', $user->id)->get();

        if ($payments->isEmpty()) {
            return 50; // Neutral for new users
        }

        $total = $payments->count();
        $onTime = $payments->where('status', 'paid')->count();
        $late = $payments->where('status', 'late')->count();
        $missed = $payments->where('status', 'missed')->count();

        // Calculate score (0-100)
        $onTimeRate = $total > 0 ? $onTime / $total : 0;
        $lateRate = $total > 0 ? $late / $total : 0;
        $missedRate = $total > 0 ? $missed / $total : 0;

        $score = ($onTimeRate * 100) - ($lateRate * 30) - ($missedRate * 70);
        $score = max(0, min(100, $score));

        // Bonus for long payment history
        if ($total >= 12) {
            $score = min(100, $score + 5);
        }

        return round($score, 2);
    }

    /**
     * Calculate amounts owed score (30% weight)
     */
    private function calculateAmountsOwedScore(User $user): float
    {
        // Get active rent-to-own contracts
        $contracts = $user->rentToOwnContracts()
            ->where('status', 'active')
            ->get();

        if ($contracts->isEmpty()) {
            return 70; // Good for no debt
        }

        $totalRemaining = $contracts->sum('remaining_balance');
        $totalPaid = $contracts->sum('amount_paid');
        $totalOwed = $totalRemaining + $totalPaid;

        // Payment progress (higher = better)
        $progress = $totalOwed > 0 ? $totalPaid / $totalOwed : 0;

        // Debt-to-income ratio
        $monthlyIncome = $user->creditScore?->monthly_income_verified ?? 10000;
        $monthlyPayments = $contracts->sum('monthly_payment');
        $dti = $monthlyIncome > 0 ? $monthlyPayments / $monthlyIncome : 1;

        // Score calculation
        $progressScore = $progress * 50; // Max 50
        $dtiScore = max(0, (1 - $dti)) * 50; // Max 50

        return round(min(100, $progressScore + $dtiScore), 2);
    }

    /**
     * Calculate credit history length score (15% weight)
     */
    private function calculateCreditHistoryLengthScore(User $user): float
    {
        $months = $user->created_at->diffInMonths(now());

        // Score based on tenure (max at 24 months)
        return round(min(100, ($months / 24) * 100), 2);
    }

    /**
     * Calculate credit mix score (10% weight)
     */
    private function calculateCreditMixScore(User $user): float
    {
        $types = 0;

        // Check for different credit types
        if ($user->rentToOwnContracts()->exists()) $types++;
        if ($user->escrows()->where('status', 'completed')->exists()) $types++;
        if ($user->shortTermBookings()->exists()) $types++;
        if ($user->creditScore?->telebirr_transaction_count > 0) $types++;

        // Score based on variety (max 4 types)
        return round(($types / 4) * 100, 2);
    }

    /**
     * Calculate new credit score (10% weight)
     */
    private function calculateNewCreditScore(User $user): float
    {
        // Recent credit inquiries
        $recentInquiries = CreditScoreLog::where('user_id', $user->id)
            ->where('change_reason', 'manual_review')
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        // Recent new accounts
        $newAccounts = $user->rentToOwnContracts()
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        // Lower is better for new credit
        $inquiryScore = max(0, 100 - ($recentInquiries * 10));
        $accountScore = max(0, 100 - ($newAccounts * 15));

        return round(($inquiryScore + $accountScore) / 2, 2);
    }

    /**
     * Get detailed metrics for score
     */
    private function getDetailedMetrics(User $user): array
    {
        $payments = RentToOwnPayment::where('buyer_id', $user->id);

        return [
            'rent_to_own_payments_on_time' => (clone $payments)->where('status', 'paid')->count(),
            'rent_to_own_payments_total' => (clone $payments)->count(),
            'rent_to_own_payments_late' => (clone $payments)->where('status', 'late')->count(),
            'rent_to_own_payments_missed' => (clone $payments)->where('status', 'missed')->count(),
            'rent_to_own_default_amount' => (clone $payments)->where('status', 'missed')->sum('amount_due') ?? 0,
            'telebirr_transaction_volume' => 0, // TODO: Fetch from Telebirr
            'telebirr_transaction_count' => 0,
            'platform_engagement_months' => $user->created_at->diffInMonths(now()),
            'platform_verified_transactions' => $user->escrows()->where('status', 'completed')->count(),
            'community_references_count' => 0, // TODO: Implement references
            'community_references_positive' => 0,
            'employment_verified' => $user->creditScore?->employment_verified ?? false,
            'employment_duration_months' => $user->creditScore?->employment_duration_months ?? 0,
            'employer_name' => $user->creditScore?->employer_name,
            'monthly_income_verified' => $user->creditScore?->monthly_income_verified,
        ];
    }

    /**
     * Determine primary change reason
     */
    private function determineChangeReason(User $user): string
    {
        // Check for recent payments
        $recentPayment = RentToOwnPayment::where('buyer_id', $user->id)
            ->where('paid_at', '>=', now()->subDays(7))
            ->first();

        if ($recentPayment) {
            return $recentPayment->status === 'paid' ? 'payment_on_time' : 'payment_late';
        }

        return 'model_update';
    }

    /**
     * Get score tier
     */
    public function getTier(int $score): string
    {
        return match(true) {
            $score < 580 => 'poor',
            $score < 670 => 'fair',
            $score < 740 => 'good',
            $score < 800 => 'very_good',
            default => 'excellent',
        };
    }

    /**
     * Predict default probability
     */
    public function predictDefaultProbability(User $user): float
    {
        $creditScore = $user->creditScore;
        if (!$creditScore) {
            return 0.5; // Unknown
        }

        // Simple logistic function based on score
        $score = $creditScore->score;
        $normalizedScore = ($score - 300) / 550; // 0 to 1

        // Higher score = lower probability
        $probability = 1 / (1 + exp(10 * ($normalizedScore - 0.5)));

        return round($probability, 4);
    }

    /**
     * Get financing options based on score
     */
    public function getFinancingOptions(User $user): array
    {
        $score = $user->creditScore?->score ?? self::BASE_SCORE;

        return match(true) {
            $score >= 800 => [
                'max_financing_pct' => 95,
                'min_down_payment_pct' => 5,
                'max_term_months' => 60,
                'interest_rate' => 8.0,
                'tier' => 'excellent',
            ],
            $score >= 740 => [
                'max_financing_pct' => 90,
                'min_down_payment_pct' => 10,
                'max_term_months' => 48,
                'interest_rate' => 10.0,
                'tier' => 'very_good',
            ],
            $score >= 670 => [
                'max_financing_pct' => 85,
                'min_down_payment_pct' => 15,
                'max_term_months' => 36,
                'interest_rate' => 12.0,
                'tier' => 'good',
            ],
            $score >= 580 => [
                'max_financing_pct' => 75,
                'min_down_payment_pct' => 25,
                'max_term_months' => 24,
                'interest_rate' => 15.0,
                'tier' => 'fair',
            ],
            default => [
                'max_financing_pct' => 0,
                'min_down_payment_pct' => 100,
                'max_term_months' => 0,
                'interest_rate' => 0,
                'tier' => 'poor',
            ],
        };
    }
}