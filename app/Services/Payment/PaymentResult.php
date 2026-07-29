<?php

namespace App\Services\Payment;

/**
 * Ödeme denemesinin sonucu.
 *
 * Başarısız ödeme bir istisna değil, beklenen bir sonuçtur — bu yüzden
 * exception fırlatmak yerine sonuç nesnesi dönüyoruz.
 */
final readonly class PaymentResult
{
    private function __construct(
        public bool $success,
        public string $code,
        public string $message,
        public ?string $transactionId = null,
    ) {}

    public static function success(string $transactionId): self
    {
        return new self(true, 'approved', 'Ödeme onaylandı.', $transactionId);
    }

    public static function failure(string $code, string $message): self
    {
        return new self(false, $code, $message);
    }
}
