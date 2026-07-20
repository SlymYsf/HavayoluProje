<?php

namespace App\Services\Pricing\Strategies;

use App\Models\Flight;
use App\Services\DemandForecastService;
use App\Services\Pricing\Contracts\PricingStrategyInterface;

class DemandBasedPricingStrategy implements PricingStrategyInterface
{
    public function __construct(private DemandForecastService $forecastService) {}

    public function isApplicable(Flight $flight, string $cabinClass): bool
    {
        return $this->forecastService->isReliable($flight->route, $cabinClass);
    }

    public function calculatePrice(Flight $flight, string $cabinClass): int
    {
        $capacityRemaining = $flight->aircraft->total_capacity - $flight->sold_seats;

        $price = $this->forecastService->calculateOptimalPrice($flight->route, $cabinClass, $capacityRemaining);

        if ($price === null) {
            throw new \RuntimeException('isApplicable true dedi ama fiyat hesaplanamadı — tutarsızlık.');
        }

        return (int) round(max(0, $price));
    }
}
