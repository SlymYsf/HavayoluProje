<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aircraft extends Model
{
    protected $table = 'aircrafts';

    protected $fillable = [
        'tail_number',
        'model',
        'body_type',
        'total_capacity',
        'business_seats',
        'premium_economy_seats',
        'economy_seats',
    ];

    public function flights()
    {
        return $this->hasMany(Flight::class);
    }

}
