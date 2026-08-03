<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'flight_id',
        'type',
        'title',
        'body',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    /** Yayınlanmış ve süresi dolmamış duyurular. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now())
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
