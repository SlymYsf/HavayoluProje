<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\TicketManagementController;
use App\Http\Controllers\FlightStatusController;

Route::post('/api/checkin', [CheckInController::class, 'store']);
Route::get('/api/tickets/manage', [TicketManagementController::class, 'show']);
Route::post('/api/tickets/cancel', [TicketManagementController::class, 'cancel']);
Route::get('/api/flights/status', [FlightStatusController::class, 'show']);
