<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'pnr',
        'flight_id',
        'passenger_id',
        'cabin_class',
        'passenger_type',
        'seat_number',
        'final_price',
        'status',
        'checked_in_at',
        'reminder_sent_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function compensation()
    {
        return $this->hasOne(Compensation::class);
    }
}
