<?php

namespace App\Services\Payment;

interface PaymentGatewayInterface
{
    /**
     * Kartı tahsil eder.
     *
     * @param array{holder: string, number: string, expiry: string, cvv: string} $card
     * @param int    $amount    Kuruş değil, tam TL (projede fiyatlar tam sayı)
     * @param string $reference Mutabakat için sipariş referansı
     */
    public function charge(array $card, int $amount, string $reference): PaymentResult;
}
