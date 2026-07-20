<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemandObservation extends Model
{
    protected $fillable = [
        'route_id',
        'cabin_class',
        'observation_date',
        'is_weekend',
        'price',
        'capacity_remaining',
        'seats_sold',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
}
