<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RentToOwnContract;
use App\Services\AI\CreditScoringService;
use Illuminate\Http\Request;

class BankApiController extends Controller
{
    public function __construct(
        private CreditScoringService $creditService
    ) {}

    /**
     * Query credit score for a user
     * GET /api/v1/bank/credit-score/{user}
     *
     * Auth: Bank API middleware
     * Requires: user_id, valid API key, signed request
     */
    public function creditScore(Request $request, User $user)
    {
        $bank = $request->get('_bank');

        // Log the query
        \Log::info('Bank API credit score query', [
            'bank_id' => $bank->id,
            'bank_name' => $bank->name,
            'queried_user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        $creditScore = $user->creditScore;

        if (!$creditScore) {
            $creditScore = $this->creditService->computeScore($user);
        }

        // Sanitize PII based on bank agreement
        return response()->json([
            'user_id' => $user->id,
            'score' => $creditScore->score,
            'tier' => $creditScore->score_tier,
            'default_probability' => $this->creditService->predictDefaultProbability($user),
            'financing_options' => $this->creditService->getFinancingOptions($user),
            'payment_summary' => [
                'on_time' => $creditScore->rent_to_own_payments_on_time,
                'late' => $creditScore->rent_to_own_payments_late,
                'missed' => $creditScore->rent_to_own_payments_missed,
                'total' => $creditScore->rent_to_own_payments_total,
            ],
            'computed_at' => $creditScore->computed_at->toIso8601String(),
            'valid_until' => $creditScore->computed_at->addDays(30)->toIso8601String(),
            'model_version' => $creditScore->model_version,
        ]);
    }

    /**
     * Submit approval decision
     * POST /api/v1/bank/rent-to-own/approval
     */
    public function approvalDecision(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'contract_id' => 'required|exists:rent_to_own_contracts,id',
            'decision' => 'required|in:approved,rejected',
            'approved_amount' => 'required_if:decision,approved|numeric|min:0',
            'interest_rate' => 'required_if:decision,approved|numeric|min:0|max:100',
            'term_months' => 'required_if:decision,approved|integer|min:1|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);

        $bank = $request->get('_bank');
        $contract = RentToOwnContract::findOrFail($validated['contract_id']);

        // Verify user matches contract
        if ($contract->buyer_id !== $validated['user_id']) {
            return response()->json([
                'error' => 'Contract buyer does not match user_id.',
            ], 422);
        }

        \DB::transaction(function () use ($contract, $validated, $bank) {
            if ($validated['decision'] === 'approved') {
                $contract->update([
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => now()->addMonths($validated['term_months']),
                    'next_payment_date' => now()->addMonth(),
                    'credit_assessment' => array_merge(
                        $contract->credit_assessment ?? [],
                        [
                            'approved_by_bank' => $bank->name,
                            'approved_amount' => $validated['approved_amount'],
                            'interest_rate' => $validated['interest_rate'],
                            'term_months' => $validated['term_months'],
                            'approved_at' => now()->toIso8601String(),
                        ]
                    ),
                ]);

                // Notify buyer
                $contract->buyer->notify(new \App\Notifications\RentToOwnApprovedNotification($contract, $bank));
            } else {
                $contract->update([
                    'status' => 'cancelled',
                    'credit_assessment' => array_merge(
                        $contract->credit_assessment ?? [],
                        [
                            'rejected_by_bank' => $bank->name,
                            'rejection_reason' => $validated['notes'] ?? 'Not specified',
                            'rejected_at' => now()->toIso8601String(),
                        ]
                    ),
                ]);

                // Notify buyer
                $contract->buyer->notify(new \App\Notifications\RentToOwnRejectedNotification($contract, $bank, $validated['notes'] ?? null));
            }
        });

        return response()->json([
            'success' => true,
            'contract_id' => $contract->id,
            'status' => $contract->fresh()->status,
        ]);
    }

    /**
     * Portfolio risk assessment
     * GET /api/v1/bank/portfolio/risk
     */
    public function portfolioRisk(Request $request)
    {
        $bank = $request->get('_bank');

        // Get all active contracts originated via this bank
        $contracts = RentToOwnContract::where('status', 'active')
            ->whereJsonContains('credit_assessment->approved_by_bank', $bank->name)
            ->get();

        $totalExposure = $contracts->sum('remaining_balance');
        $avgCreditScore = $contracts->avg('credit_score_at_approval');
        $lateCount = $contracts->filter(fn ($c) => $c->late_payments > 0)->count();
        $defaultCount = $contracts->filter(fn ($c) => $c->missed_payments >= 3)->count();

        return response()->json([
            'bank' => $bank->name,
            'portfolio' => [
                'total_contracts' => $contracts->count(),
                'total_exposure' => $totalExposure,
                'average_credit_score' => round($avgCreditScore ?? 0),
                'contracts_with_late_payments' => $lateCount,
                'contracts_in_default' => $defaultCount,
                'late_rate_pct' => $contracts->count() > 0 ? round(($lateCount / $contracts->count()) * 100, 2) : 0,
                'default_rate_pct' => $contracts->count() > 0 ? round(($defaultCount / $contracts->count()) * 100, 2) : 0,
            ],
            'by_score_tier' => $contracts->groupBy('credit_score_at_approval')
                ->map->count()
                ->toArray(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Payment webhook from bank
     * POST /api/v1/bank/webhook/payment
     */
    public function paymentWebhook(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:rent_to_own_contracts,id',
            'amount' => 'required|numeric|min:0',
            'reference' => 'required|string|max:255',
            'paid_at' => 'required|date',
            'status' => 'required|in:success,failed,pending',
        ]);

        if ($validated['status'] !== 'success') {
            return response()->json(['received' => true]);
        }

        $contract = RentToOwnContract::findOrFail($validated['contract_id']);

        // Find next pending payment
        $payment = $contract->payments()
            ->where('status', 'pending')
            ->orderBy('payment_number')
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'No pending payments'], 422);
        }

        app(\App\Services\RentToOwnService::class)->recordPayment($contract, $payment, [
            'amount' => $validated['amount'],
            'method' => 'bank_transfer',
            'reference' => $validated['reference'],
        ]);

        return response()->json([
            'success' => true,
            'payment_recorded' => true,
        ]);
    }
}