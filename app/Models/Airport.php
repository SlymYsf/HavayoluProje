<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = [
        'iata_code',
        'city',
        'country',
        'is_domestic',
        'is_hub',
    ];

    public function originRoutes()
    {
        return $this->hasMany(Route::class, 'origin_airport_id');
    }

    public function destinationRoutes()
    {
        return $this->hasMany(Route::class, 'destination_airport_id');
    }
}
