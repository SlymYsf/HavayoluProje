<?php

use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\TicketManagementController;
use App\Http\Controllers\FlightStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FlightSearchController::class, 'index']);

Route::get('/api/airports', [FlightSearchController::class, 'airports']);
Route::get('/api/flights/search', [FlightSearchController::class, 'search']);
Route::post('/api/tickets', [TicketController::class, 'store']);

Route::post('/api/checkin', [CheckInController::class, 'store']);
Route::get('/api/tickets/manage', [TicketManagementController::class, 'show']);
Route::post('/api/tickets/cancel', [TicketManagementController::class, 'cancel']);
Route::get('/api/flights/status', [FlightStatusController::class, 'show']);
