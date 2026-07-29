<?php

namespace App\Services\Payment;

/**
 * Sahte ödeme ağ geçidi.
 *
 * Gerçek bir tahsilat yapmaz, hiçbir kart verisi saklamaz. Kart numaralarını
 * iyzico'nun sandbox test kartı listesine göre değerlendirir; böylece hata
 * senaryoları gerçek bir sağlayıcının ürettiği durumlarla aynı olur.
 * Kaynak: https://docs.iyzico.com/ek-bilgiler/test-kartlari
 */
class MockPaymentGateway implements PaymentGatewayInterface
{
    /** Hata üreten test kartları → kullanıcıya gösterilecek mesaj. */
    private const DECLINE_CARDS = [
        '4111111111111129' => ['insufficient_funds', 'Kartınızda yeterli bakiye bulunmuyor.'],
        '4129111111111111' => ['do_not_honour',      'Kartınız bu işlemi onaylamadı. Bankanızla görüşün.'],
        '4128111111111112' => ['invalid_transaction','Geçersiz işlem. Lütfen kart bilgilerinizi kontrol edin.'],
        '4127111111111113' => ['lost_card',          'Bu kart kayıp olarak bildirilmiş.'],
        '4126111111111114' => ['stolen_card',        'Bu kart çalıntı olarak bildirilmiş.'],
        '4125111111111115' => ['expired_card',       'Kartın son kullanma tarihi geçmiş.'],
        '4124111111111116' => ['invalid_cvv',        'Güvenlik kodu (CVV) hatalı.'],
        '4123111111111117' => ['not_permitted',      'Bu işleme kart sahibi için izin verilmiyor.'],
        '4122111111111118' => ['terminal_denied',    'Bu işleme izin verilmiyor. Bankanızla görüşün.'],
        '4121111111111119' => ['fraud_suspect',      'İşlem güvenlik nedeniyle reddedildi.'],
        '4120111111111110' => ['pickup_card',        'Kartınız kullanılamıyor. Bankanızla görüşün.'],
        '4130111111111118' => ['general_error',      'Ödeme alınamadı. Lütfen daha sonra tekrar deneyin.'],
        '4151111111111112' => ['threeds_failed',     '3D Secure doğrulaması başlatılamadı.'],
    ];

    public function charge(array $card, int $amount, string $reference): PaymentResult
    {
        $number = preg_replace('/\D/', '', $card['number'] ?? '');

        if (trim($card['holder'] ?? '') === '') {
            return PaymentResult::failure('invalid_holder', 'Kart üzerindeki ismi girin.');
        }

        if (! $this->passesLuhn($number)) {
            return PaymentResult::failure('invalid_number', 'Kart numarası geçersiz.');
        }

        if (! $this->isExpiryValid($card['expiry'] ?? '')) {
            return PaymentResult::failure('invalid_expiry', 'Son kullanma tarihi geçersiz ya da geçmiş.');
        }

        $cvvLength = $this->brand($number) === 'amex' ? 4 : 3;

        if (! preg_match('/^\d{' . $cvvLength . '}$/', $card['cvv'] ?? '')) {
            return PaymentResult::failure('invalid_cvv', "Güvenlik kodu {$cvvLength} haneli olmalıdır.");
        }

        if (isset(self::DECLINE_CARDS[$number])) {
            [$code, $message] = self::DECLINE_CARDS[$number];
            return PaymentResult::failure($code, $message);
        }

        // Luhn'dan geçen ve reddedilme listesinde olmayan her kart onaylanır.
        return PaymentResult::success('MOCK-' . strtoupper(bin2hex(random_bytes(6))));
    }

    /** Kart markası — CVV uzunluğu ve ileride gösterim için. */
    public function brand(string $number): string
    {
        return match (true) {
            (bool) preg_match('/^4/', $number)                       => 'visa',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $number)         => 'mastercard',
            (bool) preg_match('/^3[47]/', $number)                   => 'amex',
            (bool) preg_match('/^(9792|65|36)/', $number)            => 'troy',
            default                                                  => 'unknown',
        };
    }

    /** Luhn kontrol algoritması — kart numarasının yazım hatası içermediğini doğrular. */
    private function passesLuhn(string $number): bool
    {
        if (! preg_match('/^\d{13,19}$/', $number)) {
            return false;
        }

        $sum = 0;
        $double = false;

        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];

            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }

    /** AA/YY biçimi, geçerli ay ve içinde bulunulan aydan geri değil. */
    private function isExpiryValid(string $expiry): bool
    {
        if (! preg_match('#^(\d{2})/(\d{2})$#', trim($expiry), $m)) {
            return false;
        }

        $month = (int) $m[1];
        $year  = 2000 + (int) $m[2];

        if ($month < 1 || $month > 12) {
            return false;
        }

        return now()->startOfMonth()->lte(\Carbon\Carbon::create($year, $month, 1));
    }
}
