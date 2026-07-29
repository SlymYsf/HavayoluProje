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
}
