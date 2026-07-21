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
        $this->app->singleton(\App\Services\TicketService::class);
        $this->app->singleton(\App\Services\CompensationService::class);
        $this->app->singleton(\App\Services\FlightScheduleService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
