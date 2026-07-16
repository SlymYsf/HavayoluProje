<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    protected $fillable = [
        'iata_code',
        'city',
        'country',
        'is_domestic',
        'is_hub',
    ];
}
