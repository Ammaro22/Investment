<?php

namespace App\Providers;

use App\Services\AutomaticInvestmentService;
use App\Services\FcmTokenProviderService;
use App\Services\FirebaseNotificationService;
use App\Services\PropertyAnalysisService;
use App\Services\UserPreferenceEngine;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
{
    $this->app->singleton(FcmTokenProviderService::class);

    $this->app->singleton(FirebaseNotificationService::class, function ($app) {
        return new FirebaseNotificationService(
            $app->make(FcmTokenProviderService::class)
        );
    });

    $this->app->bind(AutomaticInvestmentService::class, function ($app) {
        return new AutomaticInvestmentService(
            $app->make(UserPreferenceEngine::class),
            $app->make(PropertyAnalysisService::class),
            $app->make(FirebaseNotificationService::class)
        );
    });
}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
//
    }
}
