<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [
        'origin_airport_id',
        'destination_airport_id',
        'route_type',
        'base_price',
        'daily_frequency',
    ];

    /**
     * Rota mesafe kategorisi — hem süre tahmini (FlightScheduleService)
     * hem de uçak-menzil uygunluğu (FlightService::canAssignAircraft) için
     * tek doğru kaynak. base_price zaten mesafe bandına göre seed edildi,
     * onu kategoriye çeviriyoruz.
     */
    public function getRangeCategory(): string
    {
        return match (true) {
            $this->base_price <= 800  => 'short',
            $this->base_price <= 3500 => 'medium',
            $this->base_price <= 9000 => 'long',
            default                   => 'ultra_long',
        };
    }

    public function originAirport()
    {
        return $this->belongsTo(Airport::class, 'origin_airport_id');
    }

    public function destinationAirport()
    {
        return $this->belongsTo(Airport::class, 'destination_airport_id');
    }

    public function flights()
    {
        return $this->hasMany(Flight::class);
    }
}
