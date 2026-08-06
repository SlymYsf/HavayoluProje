<?php

namespace App\Services\Market\Contracts;

use Carbon\CarbonInterface;

interface ExchangeRateProviderInterface
{
    /**
     * USD/TRY satış kuru. Veri yoksa (tatil, kaynak erişilemez) null döner —
     * istisna atmaz, çağıran taraf yedek zincirine düşer.
     */
    public function usdTry(?CarbonInterface $date = null): ?float;
}
