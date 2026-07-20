<?php

namespace App\Services\Pricing\Strategies;

use App\Models\Flight;
use App\Services\Pricing\Contracts\PricingStrategyInterface;

class MultiplierPricingStrategy implements PricingStrategyInterface
{
    /** Fallback strateji — her zaman uygulanabilir. */
    public function isApplicable(Flight $flight, string $cabinClass): bool
    {
        return true;
    }

    public function calculatePrice(Flight $flight, string $cabinClass): int
    {
        $basePrice = $flight->route->base_price;

        $price = $basePrice
            * $this->getCabinMultiplier($cabinClass)
            * $this->getDayMultiplier($flight)
            * $this->getOccupancyMultiplier($flight);

        return (int) round($price);
    }

    private function getCabinMultiplier(string $cabinClass): float
    {
        return match ($cabinClass) {
            'economy'         => 1.0,
            'premium_economy' => 1.4,
            'business'        => 2.2,
            default           => throw new \InvalidArgumentException("Bilinmeyen kabin sınıfı: {$cabinClass}"),
        };
    }

    private function getDayMultiplier(Flight $flight): float
    {
        // Carbon dayOfWeekIso: 1=Pzt ... 5=Cum, 6=Cmt, 7=Paz
        $isWeekend = in_array($flight->departure_time->dayOfWeekIso, [5, 6, 7]);

        return $isWeekend ? 1.25 : 1.0;
    }

    private function getOccupancyMultiplier(Flight $flight): float
    {
        $capacity = $flight->aircraft->total_capacity;
        $rate = $capacity > 0 ? $flight->sold_seats / $capacity : 0;

        return match (true) {
            $rate <= 0.30 => 1.0,
            $rate <= 0.60 => 1.2,
            $rate <= 0.85 => 1.5,
            default       => 2.0,
        };
    }
}
