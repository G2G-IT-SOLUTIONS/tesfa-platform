<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Site health monitoring - every 5 minutes
        $schedule->job(new \App\Jobs\MonitorSiteHealthJob)
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Fraud detection scan - hourly
        $schedule->job(new \App\Jobs\DetectFraudPatternsJob)
            ->hourly()
            ->withoutOverlapping();

        // Credit score computation - nightly at 2 AM
        $schedule->job(new \App\Jobs\ComputeCreditScoreJob)
            ->dailyAt('02:00')
            ->withoutOverlapping();

        // Demand forecasting - nightly at 3 AM
        $schedule->job(new \App\Jobs\UpdateDemandForecastJob)
            ->dailyAt('03:00')
            ->withoutOverlapping();

        // Dynamic pricing - hourly
        $schedule->job(new \App\Jobs\UpdateDynamicPricingJob)
            ->hourly()
            ->withoutOverlapping();

        // Market metrics - nightly at 1 AM
        $schedule->job(new \App\Jobs\ComputeMarketMetricsJob)
            ->dailyAt('01:00')
            ->withoutOverlapping();

        // User behavior summaries - nightly at 4 AM
        $schedule->call(function () {
            $users = \App\Models\User::where('is_active', true)->get();
            $service = app(\App\Services\AI\UserBehaviorService::class);

            foreach ($users as $user) {
                $service->updateDailySummary($user);
            }
        })->dailyAt('04:00');

        // Cleanup expired data - weekly
        $schedule->call(function () {
            $service = app(\App\Services\GDPRComplianceService::class);
            $service->runRetentionCleanup();
        })->weekly();

        // Generate weekly reports - Monday at 6 AM
        $schedule->job(new \App\Jobs\GenerateWeeklyReportsJob)
            ->weeklyOn(1, '06:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}