<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Sahte SMS ağ geçidi — mesajı log'a yazar, gerçek gönderim yapmaz.
 *
 * Türkiye'deki sağlayıcılar (Netgsm, İletimerkezi) ücretli olduğu için
 * gerçek entegrasyon kapsam dışı bırakıldı. Mesaj şablonu, segment hesabı
 * ve tetikleme mantığı gerçek; yalnızca son adım sahte.
 * Gerçek sağlayıcı bu arayüzü uygulayan ikinci bir sınıfla bağlanır.
 */
class LogSmsGateway implements SmsGatewayInterface
{
    /**
     * GSM 03.38 temel karakter kümesi. Bu kümenin dışına çıkan tek bir
     * karakter bile mesajın tamamını UCS-2'ye çevirir.
     *
     * Türkçe'de ı, İ, ş, Ş, ğ, Ğ ve ç bu kümede YOKTUR — Ç, ö, Ö, ü, Ü vardır.
     */
    private const GSM7 = "@£\$¥èéùìòÇ\nØø\rÅå_ÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?"
    . "¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà"
    . "\f^{}\\[~]|€";

    public function send(string $phone, string $message): SmsResult
    {
        if (! preg_match('/^\+[1-9]\d{6,14}$/', $phone)) {
            return SmsResult::failure('Geçersiz telefon numarası: ' . $phone);
        }

        $unicode  = ! $this->isGsm7($message);
        $segments = $this->countSegments($message, $unicode);

        Log::channel('sms')->info('SMS', [
            'to'       => $phone,
            'encoding' => $unicode ? 'UCS-2' : 'GSM-7',
            'chars'    => mb_strlen($message),
            'segments' => $segments,
            'message'  => $message,
        ]);

        return SmsResult::success('LOG-' . strtoupper(bin2hex(random_bytes(5))), $segments);
    }

    /** Mesajın tamamı GSM-7 kümesinde mi? */
    private function isGsm7(string $message): bool
    {
        foreach (preg_split('//u', $message, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            if (mb_strpos(self::GSM7, $char) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Segment sayısı. Çok parçalı mesajlarda her segmentin başına
     * birleştirme başlığı eklendiği için kapasite düşer (160→153, 70→67).
     */
    private function countSegments(string $message, bool $unicode): int
    {
        $length = mb_strlen($message);
        $single = $unicode ? 70 : 160;
        $multi  = $unicode ? 67 : 153;

        return $length <= $single ? 1 : (int) ceil($length / $multi);
    }
}
