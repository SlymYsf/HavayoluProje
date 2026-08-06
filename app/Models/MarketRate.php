<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketRate extends Model
{
    protected $fillable = ['rate_date', 'usd_try', 'jet_fuel_usd', 'cpi'];

    protected $casts = [
        'rate_date'    => 'date',
        'usd_try'      => 'float',
        'jet_fuel_usd' => 'float',
        'cpi'          => 'float',
    ];
}
