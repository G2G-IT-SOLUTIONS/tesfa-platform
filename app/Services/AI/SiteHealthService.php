<?php

namespace App\Services\AI;

use App\Models\SiteHealthLog;
use App\Models\SiteHealthIncident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiteHealthService
{
    private const WARNING_THRESHOLDS = [
        'response_time' => 500,
        'error_rate' => 1,
        'cpu_usage' => 70,
        'memory_usage' => 80,
        'db_connections' => 80,
        'queue_size' => 100,
        'disk_space' => 80,
    ];

    private const CRITICAL_THRESHOLDS = [
        'response_time' => 2000,
        'error_rate' => 5,
        'cpu_usage' => 90,
        'memory_usage' => 95,
        'db_connections' => 95,
        'queue_size' => 1000,
        'disk_space' => 90,
    ];

    /**
     * Run all health checks
     */
    public function checkAllMetrics(): array
    {
        $metrics = [
            'response_time' => $this->checkResponseTime(),
            'error_rate' => $this->checkErrorRate(),
            'cpu_usage' => $this->checkCpuUsage(),
            'memory_usage' => $this->checkMemoryUsage(),
            'db_connections' => $this->checkDbConnections(),
            'queue_size' => $this->checkQueueSize(),
            'disk_space' => $this->checkDiskSpace(),
        ];

        $results = [];
        foreach ($metrics as $name => $value) {
            $results[$name] = $this->logMetric($name, $value);
        }

        // Check for anomalies
        $this->detectAnomalies();

        // Trigger alerts if needed
        $this->alertIfCritical($results);

        return $results;
    }

    /**
     * Check response time
     */
    private function checkResponseTime(): float
    {
        // Measure a simple internal request
        $start = microtime(true);
        DB::select('SELECT 1');
        $dbTime = (microtime(true) - $start) * 1000; // ms

        return round($dbTime, 2);
    }

    /**
     * Check error rate (last 5 minutes from logs)
     */
    private function checkErrorRate(): float
    {
        // TODO: Parse error logs
        return 0;
    }

    /**
     * Check CPU usage
     */
    private function checkCpuUsage(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $cores = (int) shell_exec('nproc') ?: 1;
            return round(($load[0] / $cores) * 100, 2);
        }

        return 0;
    }

    /**
     * Check memory usage
     */
    private function checkMemoryUsage(): float
    {
        $free = shell_exec('free');
        $free = (string) trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);

        $total = (int) $mem[1];
        $used = (int) $mem[2];

        return $total > 0 ? round(($used / $total) * 100, 2) : 0;
    }

    /**
     * Check database connections
     */
    private function checkDbConnections(): float
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            $connected = (int) ($result[0]->Value ?? 0);

            $maxResult = DB::select("SHOW VARIABLES LIKE 'max_connections'");
            $max = (int) ($maxResult[0]->Value ?? 100);

            return $max > 0 ? round(($connected / $max) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check queue size
     */
    private function checkQueueSize(): int
    {
        try {
            return (int) Redis::connection()->llen('queues:default');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Check disk space
     */
    private function checkDiskSpace(): float
    {
        $total = disk_total_space('/');
        $free = disk_free_space('/');

        if ($total > 0) {
            return round((($total - $free) / $total) * 100, 2);
        }

        return 0;
    }

    /**
     * Log a metric
     */
    private function logMetric(string $name, float $value): SiteHealthLog
    {
        $warning = self::WARNING_THRESHOLDS[$name] ?? 80;
        $critical = self::CRITICAL_THRESHOLDS[$name] ?? 95;

        $baseline = $this->getBaseline($name);
        $isAnomaly = $this->isAnomaly($value, $baseline);

        return SiteHealthLog::create([
            'metric_name' => $name,
            'metric_value' => $value,
            'metric_unit' => $this->getMetricUnit($name),
            'warning_threshold' => $warning,
            'critical_threshold' => $critical,
            'baseline_value' => $baseline,
            'is_anomaly' => $isAnomaly,
            'server_id' => gethostname(),
            'server_region' => 'Addis_Ababa',
        ]);
    }

    /**
     * Get 7-day baseline for metric
     */
    private function getBaseline(string $metricName): ?float
    {
        return SiteHealthLog::where('metric_name', $metricName)
            ->where('created_at', '>=', now()->subDays(7))
            ->avg('metric_value');
    }

    /**
     * Check if value is anomalous
     */
    private function isAnomaly(float $value, ?float $baseline): bool
    {
        if (!$baseline || $baseline == 0) {
            return false;
        }

        $deviation = abs($value - $baseline) / $baseline;

        return $deviation > 0.5; // 50% deviation
    }

    /**
     * Detect anomalies across all metrics
     */
    private function detectAnomalies(): void
    {
        $anomalies = SiteHealthLog::where('is_anomaly', true)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->get();

        if ($anomalies->count() >= 3) {
            // Create incident
            SiteHealthIncident::create([
                'incident_type' => 'multiple_anomalies',
                'severity' => 'high',
                'description' => "Multiple anomalies detected: {$anomalies->count()} metrics",
                'affected_metrics' => $anomalies->pluck('metric_name')->unique()->toArray(),
                'start_time' => now(),
            ]);
        }
    }

    /**
     * Alert if critical thresholds exceeded
     */
    private function alertIfCritical(array $results): void
    {
        foreach ($results as $name => $log) {
            if ($log->metric_value >= $log->critical_threshold) {
                $this->sendCriticalAlert($name, $log);
            } elseif ($log->metric_value >= $log->warning_threshold) {
                $this->sendWarningAlert($name, $log);
            }
        }
    }

    /**
     * Send critical alert
     */
    private function sendCriticalAlert(string $metric, SiteHealthLog $log): void
    {
        $message = "CRITICAL: {$metric} = {$log->metric_value} (threshold: {$log->critical_threshold})";

        // Slack
        if (config('services.slack.webhook_url')) {
            Http::post(config('services.slack.webhook_url'), [
                'text' => $message,
            ]);
        }

        // PagerDuty
        if (config('services.pagerduty.integration_key')) {
            Http::post('https://events.pagerduty.com/v2/enqueue', [
                'routing_key' => config('services.pagerduty.integration_key'),
                'event_action' => 'trigger',
                'payload' => [
                    'summary' => $message,
                    'severity' => 'critical',
                    'source' => 'tesfa-platform',
                ],
            ]);
        }

        $log->update(['alert_sent' => true, 'alert_channel' => 'slack']);

        Log::critical($message);
    }

    /**
     * Send warning alert
     */
    private function sendWarningAlert(string $metric, SiteHealthLog $log): void
    {
        $message = "WARNING: {$metric} = {$log->metric_value} (threshold: {$log->warning_threshold})";

        if (config('services.slack.webhook_url')) {
            Http::post(config('services.slack.webhook_url'), [
                'text' => $message,
            ]);
        }

        $log->update(['alert_sent' => true, 'alert_channel' => 'slack']);

        Log::warning($message);
    }

    /**
     * Get metric unit
     */
    private function getMetricUnit(string $name): string
    {
        return match($name) {
            'response_time' => 'ms',
            'error_rate', 'cpu_usage', 'memory_usage', 'disk_space' => 'percent',
            'db_connections', 'queue_size' => 'count',
            default => 'count',
        };
    }

    /**
     * Generate health report
     */
    public function generateHealthReport(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $metrics = SiteHealthLog::where('created_at', '>=', $startDate)
            ->select('metric_name', DB::raw('
                AVG(metric_value) as avg_value,
                MAX(metric_value) as max_value,
                MIN(metric_value) as min_value,
                COUNT(*) as sample_count,
                SUM(CASE WHEN is_anomaly THEN 1 ELSE 0 END) as anomaly_count
            '))
            ->groupBy('metric_name')
            ->get();

        return [
            'period' => "{$days} days",
            'generated_at' => now()->toIso8601String(),
            'metrics' => $metrics->toArray(),
            'uptime_percentage' => $this->calculateUptime($days),
        ];
    }

    /**
     * Calculate uptime percentage
     */
    private function calculateUptime(int $days): float
    {
        $totalChecks = SiteHealthLog::where('metric_name', 'response_time')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();

        if ($totalChecks === 0) {
            return 100;
        }

        $failedChecks = SiteHealthLog::where('metric_name', 'response_time')
            ->where('created_at', '>=', now()->subDays($days))
            ->where('metric_value', '>', self::CRITICAL_THRESHOLDS['response_time'])
            ->count();

        return round((($totalChecks - $failedChecks) / $totalChecks) * 100, 3);
    }
}