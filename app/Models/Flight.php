<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    /** Uçuşun satışa açık sayıldığı durumlar. */
    public const SELLABLE_STATUSES = ['Planlandı', 'Gecikmeli'];

    /** Kalkışa bu kadar dakika kala bilet satışı kapanır. */
    public const SALES_CUTOFF_MINUTES = 45;

    protected $fillable = [
        'flight_number',
        'route_id',
        'aircraft_id',
        'departure_time',
        'arrival_time',
        'status',
        'delay_minutes',
        'delay_reason',
        'sold_seats',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time'   => 'datetime',
        'delay_minutes'  => 'integer',
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

    public function isDelayed(): bool
    {
        return $this->status === 'Gecikmeli' && $this->delay_minutes > 0;
    }

    /**
     * Rötar dahil edilmiş kalkış saati.
     *
     * departure_time planlanan saati tutmaya devam eder; yolcuya gösterilen
     * tahmini saat buradan türetilir.
     */
    public function estimatedDepartureTime(): \Carbon\Carbon
    {
        return $this->isDelayed()
            ? $this->departure_time->copy()->addMinutes($this->delay_minutes)
            : $this->departure_time->copy();
    }

    public function estimatedArrivalTime(): \Carbon\Carbon
    {
        return $this->isDelayed()
            ? $this->arrival_time->copy()->addMinutes($this->delay_minutes)
            : $this->arrival_time->copy();
    }
}
