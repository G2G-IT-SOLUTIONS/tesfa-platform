<?php

namespace App\Jobs;

use App\Models\Property;
use App\Models\MarketMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeMarketMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $neighborhoods = Property::distinct('neighborhood')->pluck('neighborhood');

        foreach ($neighborhoods as $neighborhood) {
            $this->computeForNeighborhood($neighborhood);
        }
    }

    private function computeForNeighborhood(string $neighborhood): void
    {
        $properties = Property::where('neighborhood', $neighborhood)
            ->where('status', 'active')
            ->get();

        if ($properties->isEmpty()) {
            return;
        }

        $soldProperties = Property::where('neighborhood', $neighborhood)
            ->where('status', 'sold')
            ->where('sold_at', '>=', now()->subDays(90))
            ->get();

        MarketMetric::updateOrCreate(
            [
                'neighborhood' => $neighborhood,
                'property_type' => 'all',
                'metric_date' => today(),
            ],
            [
                'avg_price_per_sqm' => $properties->avg('price_per_sqm'),
                'median_price_per_sqm' => $properties->median('price_per_sqm'),
                'min_price_per_sqm' => $properties->min('price_per_sqm'),
                'max_price_per_sqm' => $properties->max('price_per_sqm'),
                'total_listings' => $properties->count(),
                'active_listings' => $properties->where('status', 'active')->count(),
                'new_listings_7d' => $properties->where('created_at', '>=', now()->subDays(7))->count(),
                'sold_listings_7d' => $soldProperties->where('sold_at', '>=', now()->subDays(7))->count(),
                'avg_days_on_market' => $soldProperties->avg(fn ($p) => $p->created_at->diffInDays($p->sold_at)),
                'total_views' => $properties->sum('view_count'),
                'total_inquiries' => $properties->sum('inquiry_count'),
                'demand_score' => $this->calculateDemandScore($properties),
            ]
        );
    }

    private function calculateDemandScore($properties): float
    {
        $views = $properties->sum('view_count');
        $inquiries = $properties->sum('inquiry_count');
        $count = max($properties->count(), 1);

        $viewScore = min(50, ($views / $count) / 2);
        $inquiryScore = min(50, ($inquiries / $count) * 10);

        return round($viewScore + $inquiryScore, 2);
    }
}