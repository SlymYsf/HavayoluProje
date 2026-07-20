<?php

namespace App\Services\Pricing;

use App\Models\Flight;
use App\Services\Pricing\Contracts\PricingStrategyInterface;
use App\Services\Pricing\Strategies\DemandBasedPricingStrategy;
use App\Services\Pricing\Strategies\MultiplierPricingStrategy;

class PricingStrategyFactory
{
    public function __construct(
        private DemandBasedPricingStrategy $demandBasedStrategy,
        private MultiplierPricingStrategy $multiplierStrategy,
    ) {}

    public function resolve(Flight $flight, string $cabinClass): PricingStrategyInterface
    {
        if ($this->demandBasedStrategy->isApplicable($flight, $cabinClass)) {
            return $this->demandBasedStrategy;
        }

        return $this->multiplierStrategy;
    }
}
