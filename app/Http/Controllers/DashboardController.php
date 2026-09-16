<?php

namespace App\Http\Controllers;

use App\Models\Escrow;
use App\Models\Property;
use App\Models\RentToOwnContract;
use App\Models\ShortTermBooking;
use App\Models\UserBehaviorLog;
use App\Models\UserBehaviorSummary;
use App\Services\AI\CreditScoringService;
use App\Services\AI\UserBehaviorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private CreditScoringService $creditService,
        private UserBehaviorService $behaviorService,
    ) {}

    /**
     * Main dashboard
     */
    public function index()
    {
        $user = auth()->user();

        // Compute stats
        $stats = [
            'properties' => Property::where('owner_id', $user->id)->count(),
            'active_escrows' => Escrow::where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })->whereIn('status', ['created', 'funded', 'documents_submitted', 'agent_verified'])->count(),
            'saved' => DB::table('saved_properties')->where('user_id', $user->id)->count(),
            'bookings' => ShortTermBooking::where('guest_id', $user->id)->whereIn('status', ['confirmed', 'checked_in'])->count(),
        ];

        // Get or compute credit score
        $creditScore = $user->creditScore;
        if (!$creditScore) {
            $creditScore = $this->creditService->computeScore($user);
        }

        // Recent activity (from behavior logs)
        $recentActivity = $this->getRecentActivity($user);

        // Recommendations
        $recommendations = $this->getRecommendations($user);

        return view('pages.dashboard', compact('stats', 'creditScore', 'recentActivity', 'recommendations'));
    }

    /**
     * Credit score details page
     */
    public function creditScore()
    {
        $user = auth()->user();

        $creditScore = $user->creditScore ?? $this->creditService->computeScore($user);

        $history = $user->creditScoreLogs()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $financingOptions = $this->creditService->getFinancingOptions($user);

        return view('pages.credit-score', compact('creditScore', 'history', 'financingOptions'));
    }

    /**
     * Get recent activity feed
     */
    private function getRecentActivity($user): \Illuminate\Support\Collection
    {
        $activities = collect();

        // Recent property views
        $recentViews = UserBehaviorLog::where('user_id', $user->id)
            ->where('event_type', 'page_view')
            ->whereNotNull('property_id')
            ->with('property')
            ->latest()
            ->limit(5)
            ->get();

        foreach ($recentViews as $view) {
            if ($view->property) {
                $activities->push([
                    'icon' => 'eye',
                    'description' => "Viewed <a href=\"" . route('properties.show', $view->property) . "\" class=\"text-[#078930] hover:underline\">{$view->property->title}</a>",
                    'time' => $view->created_at->diffForHumans(),
                    'sort' => $view->created_at,
                ]);
            }
        }

        // Recent escrow updates
        $recentEscrows = Escrow::where(function ($q) use ($user) {
            $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
        })->with('property')->latest('updated_at')->limit(5)->get();

        foreach ($recentEscrows as $escrow) {
            $activities->push([
                'icon' => 'lock-closed',
                'description' => "Escrow <a href=\"" . route('escrow.show', $escrow) . "\" class=\"text-[#078930] hover:underline\">{$escrow->escrow_number}</a> — " . ucfirst(str_replace('_', ' ', $escrow->status)),
                'time' => $escrow->updated_at->diffForHumans(),
                'sort' => $escrow->updated_at,
            ]);
        }

        // Recent RTO payments
        $recentPayments = \App\Models\RentToOwnPayment::where('buyer_id', $user->id)
            ->whereNotNull('paid_at')
            ->latest('paid_at')
            ->limit(3)
            ->get();

        foreach ($recentPayments as $payment) {
            $activities->push([
                'icon' => 'banknotes',
                'description' => "Paid " . number_format($payment->amount_paid) . " ETB for rent-to-own #{$payment->payment_number}",
                'time' => $payment->paid_at->diffForHumans(),
                'sort' => $payment->paid_at,
            ]);
        }

        return $activities->sortByDesc('sort')->take(10)->values();
    }

    /**
     * Get personalized recommendations
     */
    private function getRecommendations($user): \Illuminate\Support\Collection
    {
        $summary = UserBehaviorSummary::where('user_id', $user->id)
            ->latest('summary_date')
            ->first();

        $query = Property::query()->where('status', 'active');

        if ($summary) {
            $preferredNeighborhoods = $summary->preferred_neighborhoods ?? [];
            $preferredTypes = $summary->preferred_property_types ?? [];

            if (!empty($preferredNeighborhoods)) {
                $query->whereIn('neighborhood', $preferredNeighborhoods);
            }
            if (!empty($preferredTypes)) {
                $query->whereIn('type', $preferredTypes);
            }
            if ($summary->price_range_min) {
                $query->where('price', '>=', $summary->price_range_min * 0.8);
            }
            if ($summary->price_range_max) {
                $query->where('price', '<=', $summary->price_range_max * 1.2);
            }
        }

        return $query->with('media')
            ->orderBy('view_count', 'desc')
            ->limit(6)
            ->get();
    }
}