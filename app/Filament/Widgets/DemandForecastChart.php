<?php

namespace App\Filament\Widgets;

use App\Models\DemandForecast;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class DemandForecastChart extends ChartWidget
{
    protected static ?string $heading = 'Demand Forecast (Bole)';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 1;

    public ?string $filter = 'bole';

    protected function getData(): array
    {
        $neighborhood = match($this->filter) {
            'bole' => 'Bole',
            'cmc' => 'CMC',
            'mekanisa' => 'Mekanisa',
            'sarbet' => 'Sarbet',
            default => 'Bole',
        };

        $forecasts = DemandForecast::query()
            ->where('neighborhood', $neighborhood)
            ->where('prediction_for_date', '>=', now())
            ->where('prediction_for_date', '<=', now()->addDays(30))
            ->orderBy('prediction_for_date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Predicted Demand',
                    'data' => $forecasts->pluck('demand_index')->toArray(),
                    'borderColor' => '#078930',
                    'fill' => false,
                ],
                [
                    'label' => 'Confidence Low',
                    'data' => $forecasts->pluck('demand_index_low')->toArray(),
                    'borderColor' => '#078930',
                    'borderDash' => [5, 5],
                    'fill' => false,
                ],
                [
                    'label' => 'Confidence High',
                    'data' => $forecasts->pluck('demand_index_high')->toArray(),
                    'borderColor' => '#078930',
                    'borderDash' => [5, 5],
                    'fill' => '-1',
                ],
            ],
            'labels' => $forecasts->pluck('prediction_for_date')
                ->map(fn ($date) => Carbon::parse($date)->format('M d'))
                ->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'bole' => 'Bole',
            'cmc' => 'CMC',
            'mekanisa' => 'Mekanisa',
            'sarbet' => 'Sarbet',
        ];
    }
}