<?php

namespace App\Filament\Widgets;

use App\Models\CreditScore;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CreditScoreDistribution extends ChartWidget
{
    protected static ?string $heading = 'Credit Score Distribution';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $distribution = CreditScore::query()
            ->select('score_tier', DB::raw('count(*) as count'))
            ->groupBy('score_tier')
            ->pluck('count', 'score_tier')
            ->toArray();

        $tiers = ['poor', 'fair', 'good', 'very_good', 'excellent'];
        $data = [];
        foreach ($tiers as $tier) {
            $data[] = $distribution[$tier] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $data,
                    'backgroundColor' => [
                        '#DA020E', // Red (poor)
                        '#F59E0B', // Amber (fair)
                        '#3B82F6', // Blue (good)
                        '#078930', // Green (very good)
                        '#10B981', // Emerald (excellent)
                    ],
                ],
            ],
            'labels' => ['Poor (<580)', 'Fair (580-669)', 'Good (670-739)', 'Very Good (740-799)', 'Excellent (800+)'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}