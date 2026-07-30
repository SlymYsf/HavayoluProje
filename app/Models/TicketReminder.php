<?php

namespace App\Models;

use App\Notifications\ReminderType;
use Illuminate\Database\Eloquent\Model;

class TicketReminder extends Model
{
    protected $fillable = [
        'pnr',
        'flight_id',
        'type',
        'scheduled_at',
        'status',
        'queued_at',
        'sent_at',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'queued_at'    => 'datetime',
        'sent_at'      => 'datetime',
        'type'         => ReminderType::class,
    ];

    public function flight()
    {
        return $this->belongsTo(Flight::class);
    }
}
