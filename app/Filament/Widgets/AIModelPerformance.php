<?php

namespace App\Filament\Widgets;

use App\Models\AIPrediction;
use App\Models\ModelPerformanceMetric;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AIModelPerformance extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $avmMetrics = $this->getModelMetrics('price_prediction');
        $fraudMetrics = $this->getModelMetrics('fraud_probability');
        $creditMetrics = $this->getModelMetrics('credit_score');
        $demandMetrics = $this->getModelMetrics('demand_forecast');

        return [
            Stat::make('AVM Accuracy', number_format($avmMetrics['accuracy'] * 100, 1) . '%')
                ->description($avmMetrics['total_predictions'] . ' predictions')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color($avmMetrics['accuracy'] > 0.9 ? 'success' : 'warning'),

            Stat::make('Fraud Detection', number_format($fraudMetrics['accuracy'] * 100, 1) . '%')
                ->description($fraudMetrics['total_predictions'] . ' alerts')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color($fraudMetrics['accuracy'] > 0.95 ? 'success' : 'warning'),

            Stat::make('Credit Scoring', number_format($creditMetrics['accuracy'] * 100, 1) . '%')
                ->description($creditMetrics['total_predictions'] . ' scores')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($creditMetrics['accuracy'] > 0.75 ? 'success' : 'warning'),

            Stat::make('Demand Forecast', number_format($demandMetrics['accuracy'] * 100, 1) . '%')
                ->description('MAPE: ' . number_format((1 - $demandMetrics['accuracy']) * 100, 1) . '%')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($demandMetrics['accuracy'] > 0.8 ? 'success' : 'warning'),
        ];
    }

    private function getModelMetrics(string $type): array
    {
        $predictions = AIPrediction::where('prediction_type', $type)
            ->where('is_validated', true)
            ->get();

        return [
            'accuracy' => $predictions->avg('accuracy') ?? 0.85,
            'total_predictions' => $predictions->count(),
            'last_trained' => $predictions->max('computed_at'),
        ];
    }
}