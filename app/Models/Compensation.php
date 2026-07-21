<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compensation extends Model
{
    protected $table = 'compensations';
    protected $fillable = [
        'ticket_id',
        'reason',
        'compensation_amount',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
