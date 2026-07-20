<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\DemandForecastService::class);
        $this->app->singleton(\App\Services\Pricing\Strategies\MultiplierPricingStrategy::class);
        $this->app->singleton(\App\Services\Pricing\Strategies\DemandBasedPricingStrategy::class);
        $this->app->singleton(\App\Services\Pricing\PricingStrategyFactory::class);
        $this->app->singleton(\App\Services\Pricing\PricingService::class);
        $this->app->singleton(\App\Services\FlightSearchService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
