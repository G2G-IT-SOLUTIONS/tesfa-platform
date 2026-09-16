<?php

namespace App\Filament\Widgets;

use App\Models\SiteHealthLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiteHealthMonitor extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $latestMetrics = SiteHealthLog::query()
            ->where('created_at', '>=', now()->subMinutes(10))
            ->selectRaw('metric_name, AVG(metric_value) as avg_value')
            ->groupBy('metric_name')
            ->get()
            ->keyBy('metric_name');

        $responseTime = $latestMetrics->get('response_time')?->avg_value ?? 0;
        $errorRate = $latestMetrics->get('error_rate')?->avg_value ?? 0;
        $cpuUsage = $latestMetrics->get('cpu_usage')?->avg_value ?? 0;
        $memoryUsage = $latestMetrics->get('memory_usage')?->avg_value ?? 0;
        $queueSize = $latestMetrics->get('queue_size')?->avg_value ?? 0;

        $anomalies = SiteHealthLog::where('is_anomaly', true)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        return [
            Stat::make('Response Time', number_format($responseTime, 0) . ' ms')
                ->description('Average last 10 min')
                ->descriptionIcon('heroicon-m-clock')
                ->color($responseTime < 500 ? 'success' : ($responseTime < 2000 ? 'warning' : 'danger')),

            Stat::make('Error Rate', number_format($errorRate, 2) . '%')
                ->description('Last 10 min')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($errorRate < 1 ? 'success' : ($errorRate < 5 ? 'warning' : 'danger')),

            Stat::make('CPU Usage', number_format($cpuUsage, 1) . '%')
                ->description('Current')
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color($cpuUsage < 70 ? 'success' : ($cpuUsage < 90 ? 'warning' : 'danger')),

            Stat::make('Queue Size', number_format($queueSize, 0))
                ->description('Pending jobs')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color($queueSize < 100 ? 'success' : ($queueSize < 1000 ? 'warning' : 'danger')),

            Stat::make('Anomalies (1h)', $anomalies)
                ->description('Detected deviations')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($anomalies === 0 ? 'success' : ($anomalies < 5 ? 'warning' : 'danger')),
        ];
    }
}