<?php

namespace App\Services\Market\Contracts;

use Carbon\CarbonInterface;

interface JetFuelPriceProviderInterface
{
    /** Jet A-1 spot fiyatı ($/galon). Veri yoksa null. */
    public function pricePerGallon(?CarbonInterface $date = null): ?float;
}
