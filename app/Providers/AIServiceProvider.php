<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\AVMService;
use App\Services\AI\FraudDetectionService;
use App\Services\AI\CreditScoringService;
use App\Services\AI\DemandForecastService;
use App\Services\AI\DynamicPricingService;
use App\Services\AI\SiteHealthService;
use App\Services\AI\UserBehaviorService;
use App\Services\AI\ChatbotService;
use App\Services\AI\ImageQualityService;
use App\Services\AI\DocumentOCRService;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AVMService::class);
        $this->app->singleton(FraudDetectionService::class);
        $this->app->singleton(CreditScoringService::class);
        $this->app->singleton(DemandForecastService::class);
        $this->app->singleton(DynamicPricingService::class);
        $this->app->singleton(SiteHealthService::class);
        $this->app->singleton(UserBehaviorService::class);
        $this->app->singleton(ChatbotService::class);
        $this->app->singleton(ImageQualityService::class);
        $this->app->singleton(DocumentOCRService::class);
    }

    public function boot(): void
    {
        //
    }
}