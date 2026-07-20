<?php

namespace App\Services\Pricing;

use App\Models\Flight;

class PricingService
{
    public function __construct(private PricingStrategyFactory $factory) {}

    public function calculatePrice(Flight $flight, string $cabinClass): int
    {
        return $this->factory
            ->resolve($flight, $cabinClass)
            ->calculatePrice($flight, $cabinClass);
    }
}
