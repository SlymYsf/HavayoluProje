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


/* Saatlik çalıştırıyoruz: 12:00'de kalkan uçuş 12:01'de geçmişte olur ve
   bir sonraki saatte silinir. Günlük çalıştırırsak akşam olmuş uçuşlar
   sabaha kadar listede kalır. withoutOverlapping: önceki çalışma
   bitmediyse ikincisi başlamıyor. */
Schedule::command('flights:purge')
    ->hourly()
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping();

// TCMB günün kurunu 15:30'dan sonra yayınlıyor; 16:00 güvenli aralık.
Schedule::command('market:sync')->dailyAt('16:00')->withoutOverlapping();
