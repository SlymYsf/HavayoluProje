<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'iso_code',
        'name',
        'dial_code',
        'sort_order',
    ];

    /**
     * Bayrak görseli URL'i — ISO kodundan türetilir, veritabanında saklanmaz.
     * flagcdn.com ücretsiz ve anahtarsızdır, ISO 3166-1 alpha-2 kodunu kullanır.
     */
    public function getFlagUrlAttribute(): string
    {
        return 'https://flagcdn.com/w40/' . strtolower($this->iso_code) . '.png';
    }

    /**
     * Ülkenin görüntülenecek adı.
     *
     * Türkçe ad veritabanında seed edilmiş durumda; diğer diller ISO
     * kodundan intl eklentisiyle türetiliyor, böylece her dil için ayrı
     * sütun ve seeder gerekmiyor.
     */
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'tr') {
            return $this->name;
        }

        $translated = \Locale::getDisplayRegion('-' . $this->iso_code, $locale);

        // Bilinmeyen kodlarda intl kodun kendisini döndürüyor; o durumda
        // Türkçe ada geri dönmek boş isim göstermekten iyi.
        return ($translated && $translated !== $this->iso_code) ? $translated : $this->name;
    }
}
