<?php

namespace App\Services\Pricing\Contracts;

use App\Models\Flight;

interface PricingStrategyInterface
{
    /** Bu strateji, verilen uçuş + kabin sınıfı için kullanılabilir mi? */
    public function isApplicable(Flight $flight, string $cabinClass): bool;

    /** Nihai bileti hesaplar. */
    public function calculatePrice(Flight $flight, string $cabinClass): int;
}
