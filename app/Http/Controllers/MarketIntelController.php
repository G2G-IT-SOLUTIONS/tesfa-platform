<?php

namespace App\Http\Controllers;

use App\Models\MarketMetric;
use App\Models\Property;
use App\Models\DemandForecast;
use App\Services\MarketIntelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarketIntelController extends Controller
{
    public function __construct(
        private MarketIntelService $marketService
    ) {}

    /**
     * Market intelligence overview
     */
    public function index(Request $request)
    {
        $neighborhood = $request->query('neighborhood');

        $overview = Cache::remember('market_overview', 3600, function () {
            $metrics = MarketMetric::where('metric_date', today())->get();

            return [
                'total_listings' => Property::where('status', 'active')->count(),
                'avg_price_per_sqm' => round($metrics->avg('avg_price_per_sqm') ?? 0),
                'avg_days_on_market' => round($metrics->avg('avg_days_on_market') ?? 0, 1),
                'demand_score' => round($metrics->avg('demand_score') ?? 0),
            ];
        });

        $priceTrends = Cache::remember('price_trends_' . ($neighborhood ?? 'all'), 3600, function () use ($neighborhood) {
            return $this->marketService->getPriceTrends($neighborhood, 12);
        });

        $neighborhoods = MarketMetric::where('metric_date', today())
            ->orderBy('avg_price_per_sqm', 'desc')
            ->get();

        $topNeighborhoods = $neighborhoods->take(5);

        return view('pages.market-intel.index', compact(
            'overview', 'priceTrends', 'neighborhoods', 'topNeighborhoods'
        ));
    }

    /**
     * Neighborhood detail page
     */
    public function neighborhood(string $neighborhood)
    {
        $metrics = MarketMetric::where('neighborhood', $neighborhood)
            ->latest('metric_date')
            ->first();

        if (!$metrics) {
            // Generate metrics on the fly
            $metrics = $this->marketService->computeForNeighborhood($neighborhood);
        }

        $priceHistory = $this->marketService->getPriceTrends($neighborhood, 12);

        $forecast = DemandForecast::where('neighborhood', $neighborhood)
            ->where('prediction_for_date', '>=', today())
            ->where('prediction_for_date', '<=', now()->addDays(30))
            ->orderBy('prediction_for_date')
            ->get();

        $properties = Property::where('neighborhood', $neighborhood)
            ->where('status', 'active')
            ->with('media')
            ->limit(8)
            ->get();

        return view('pages.market-intel.neighborhood', compact(
            'neighborhood', 'metrics', 'priceHistory', 'forecast', 'properties'
        ));
    }

    /**
     * Trends page (all neighborhoods)
     */
    public function trends(Request $request)
    {
        $period = $request->query('period', '12m');
        $months = match ($period) {
            '3m' => 3,
            '6m' => 6,
            '12m' => 12,
            '24m' => 24,
            default => 12,
        };

        $allTrends = MarketMetric::select('neighborhood')
            ->distinct()
            ->pluck('neighborhood')
            ->map(function ($neighborhood) use ($months) {
                return [
                    'neighborhood' => $neighborhood,
                    'data' => $this->marketService->getPriceTrends($neighborhood, $months),
                ];
            });

        return view('pages.market-intel.trends', compact('allTrends', 'period'));
    }

    /**
     * Demand heatmap (AJAX)
     */
    public function heatmap()
    {
        $data = DemandForecast::where('prediction_for_date', today()->addDays(7))
            ->select('neighborhood', 'demand_index')
            ->get()
            ->map(fn ($row) => [
                'neighborhood' => $row->neighborhood,
                'demand' => $row->demand_index,
            ]);

        return response()->json($data);
    }
}