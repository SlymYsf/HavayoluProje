<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('flights:extend-schedule')->dailyAt('00:05');

// Check-in penceresi kalkıştan 24 saat önce açılır; 15 dakikalık tarama
// gecikmeyi kabul edilebilir seviyede tutuyor.

Schedule::command('reminders:dispatch')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
