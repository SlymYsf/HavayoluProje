<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Passenger extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'birth_date',
        'tc_or_passport_no',
        'email',
        'phone',
        'user_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
