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
    ];
}
