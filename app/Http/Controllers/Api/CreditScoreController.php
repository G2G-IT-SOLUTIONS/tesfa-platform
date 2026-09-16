<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AI\CreditScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CreditScoreController extends Controller
{
    public function __construct(
        private CreditScoringService $creditService
    ) {}

    /**
     * Get current user's credit score
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $creditScore = $user->creditScore;

        if (!$creditScore) {
            $creditScore = $this->creditService->computeScore($user);
        }

        return response()->json([
            'score' => $creditScore->score,
            'tier' => $creditScore->score_tier,
            'components' => [
                'payment_history' => $creditScore->payment_history_score,
                'amounts_owed' => $creditScore->amounts_owed_score,
                'credit_history_length' => $creditScore->credit_history_length_score,
                'credit_mix' => $creditScore->credit_mix_score,
                'new_credit' => $creditScore->new_credit_score,
            ],
            'payment_stats' => [
                'on_time' => $creditScore->rent_to_own_payments_on_time,
                'total' => $creditScore->rent_to_own_payments_total,
                'late' => $creditScore->rent_to_own_payments_late,
                'missed' => $creditScore->rent_to_own_payments_missed,
            ],
            'score_change' => [
                'last_30_days' => $creditScore->score_change_last_30_days,
                'last_90_days' => $creditScore->score_change_last_90_days,
            ],
            'computed_at' => $creditScore->computed_at->toIso8601String(),
            'next_computation' => $creditScore->next_computation_at->toIso8601String(),
        ]);
    }

    /**
     * Get credit score history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $logs = $user->creditScoreLogs()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'date' => $log->created_at->toIso8601String(),
                'old_score' => $log->old_score,
                'new_score' => $log->new_score,
                'change' => $log->score_change,
                'reason' => $log->change_reason,
            ]);

        return response()->json(['history' => $logs]);
    }

    /**
     * Get financing options based on score
     */
    public function financingOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $options = $this->creditService->getFinancingOptions($user);

        return response()->json($options);
    }

    /**
     * Bank API: Query credit score for user
     */
    public function bankQuery(Request $request, User $user): JsonResponse
    {
        $creditScore = $user->creditScore;

        if (!$creditScore) {
            return response()->json(['error' => 'No credit score available'], 404);
        }

        $defaultProbability = $this->creditService->predictDefaultProbability($user);

        return response()->json([
            'user_id' => $user->id,
            'score' => $creditScore->score,
            'tier' => $creditScore->score_tier,
            'default_probability' => $defaultProbability,
            'financing_options' => $this->creditService->getFinancingOptions($user),
            'computed_at' => $creditScore->computed_at->toIso8601String(),
            'valid_until' => $creditScore->computed_at->addDays(30)->toIso8601String(),
        ]);
    }

    /**
     * Bank API: Submit approval decision
     */
    public function approvalDecision(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'contract_id' => 'required|exists:rent_to_own_contracts,id',
            'decision' => 'required|in:approved,rejected',
            'approved_amount' => 'required_if:decision,approved|numeric',
            'interest_rate' => 'required_if:decision,approved|numeric',
            'term_months' => 'required_if:decision,approved|integer',
            'notes' => 'nullable|string',
        ]);

        // Process decision
        $contract = \App\Models\RentToOwnContract::find($validated['contract_id']);

        if ($validated['decision'] === 'approved') {
            $contract->update([
                'status' => 'active',
                'start_date' => now(),
            ]);
        } else {
            $contract->update([
                'status' => 'cancelled',
            ]);
        }

        return response()->json(['success' => true]);
    }
}