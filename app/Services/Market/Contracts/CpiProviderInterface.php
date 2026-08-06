<?php

namespace App\Services\Market\Contracts;

use Carbon\CarbonInterface;

interface CpiProviderInterface
{
    /**
     * Verilen tarihte yayınlanmış son TÜFE endeksi. Veri yoksa null.
     */
    public function index(?CarbonInterface $date = null): ?float;
}
