<?php

namespace App\Services\Sms;

/**
 * SMS gönderim sonucu.
 *
 * Segment sayısı taşınıyor çünkü ücretlendirme segment başınadır ve
 * Türkçe karakterler mesajı Unicode moduna sokup segment başına düşen
 * karakter sayısını 160'tan 70'e indirir.
 */
final readonly class SmsResult
{
    private function __construct(
        public bool $success,
        public string $message,
        public int $segments = 0,
        public ?string $messageId = null,
    ) {}

    public static function success(string $messageId, int $segments): self
    {
        return new self(true, 'Gönderildi.', $segments, $messageId);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
