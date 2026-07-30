<?php

namespace App\Support;

/**
 * Türkçe metni GSM-7 karakter kümesine indirger.
 *
 * Sebep: ı, İ, ş, Ş, ğ, Ğ, ç karakterleri GSM-7'de yoktur; tek biri bile
 * mesajın tamamını UCS-2'ye çevirir ve segment kapasitesini 160'tan 70'e
 * düşürür — yani maliyeti ikiye katlar. Bildirim SMS'lerinde sektör
 * uygulaması ASCII kullanmaktır.
 *
 * Not: Ç, ö, Ö, ü, Ü aslında GSM-7'de VAR, ama tutarlı görünüm için
 * onları da dönüştürüyoruz.
 */
final class TurkishAscii
{
    private const MAP = [
        'ç' => 'c', 'Ç' => 'C',
        'ğ' => 'g', 'Ğ' => 'G',
        'ı' => 'i', 'I' => 'I', 'İ' => 'I',
        'ö' => 'o', 'Ö' => 'O',
        'ş' => 's', 'Ş' => 'S',
        'ü' => 'u', 'Ü' => 'U',
    ];

    public static function convert(string $text): string
    {
        return strtr($text, self::MAP);
    }
}
