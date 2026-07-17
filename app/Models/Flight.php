<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    protected $fillable = [
        'flight_number',
        'route_id',
        'aircraft_id',
        'departure_time',
        'arrival_time',
        'status',
        'sold_seats',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time'   => 'datetime',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function aircraft()
    {
        return $this->belongsTo(Aircraft::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
