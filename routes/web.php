<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CountryCodeController;
use App\Http\Controllers\FareCalendarController;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\FlightStatusController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketManagementController;
use Illuminate\Support\Facades\Route;

/* ===== SAYFALAR ===== */

Route::get('/', [FlightSearchController::class, 'index']);

Route::get('/ucus-sonuclari', fn () => view('flights.results'))->name('flights.results');
Route::get('/check-in', fn () => view('flights.checkin'))->name('flights.checkin');

/* ===== API ===== */

Route::get('/api/airports', [FlightSearchController::class, 'airports']);
Route::get('/api/airports/{airport}/destinations', [FlightSearchController::class, 'destinations']);
Route::get('/api/flights/search', [FlightSearchController::class, 'search']);
Route::get('/api/flights/status', [FlightStatusController::class, 'show']);
Route::get('/api/fares/calendar', [FareCalendarController::class, 'strip']);
Route::get('/api/country-codes', [CountryCodeController::class, 'index']);

Route::post('/api/tickets', [TicketController::class, 'store']);
Route::post('/api/checkin', [CheckInController::class, 'store']);
Route::get('/api/tickets/manage', [TicketManagementController::class, 'show']);
Route::post('/api/tickets/cancel', [TicketManagementController::class, 'cancel']);

/* ===== REZERVASYON AKIŞI ===== */

// Bu sayfa sayacı BAŞLATIR, o yüzden middleware yok
Route::get('/yolcu-bilgileri', [ReservationController::class, 'passengers'])
    ->name('reservation.passengers');

// Bundan sonraki her adım süre kontrolünden geçer
Route::middleware('reservation.timeout')->group(function () {
    Route::post('/rezervasyon/yolcular', [ReservationController::class, 'storePassengers'])
        ->name('reservation.passengers.store');

    Route::get('/odeme', [ReservationController::class, 'payment'])
        ->name('reservation.payment');

    Route::post('/rezervasyon/tamamla', [ReservationController::class, 'complete'])
        ->name('reservation.complete');
});

// Onay sayfası süre kontrolü DIŞINDA: rezervasyon tamamlandığında sayaç
// session'dan silindiği için middleware bu sayfayı da engellerdi.
Route::get('/rezervasyon/onay/{pnr}', [ReservationController::class, 'confirmation'])
    ->name('reservation.confirmation');

// Sayaç sıfırlandığında tarayıcı buraya yönlenir; session temizlenir ve
// kullanıcı ana sayfada neden döndüğünü açıklayan bir mesajla karşılanır.
Route::get('/rezervasyon/zaman-asimi', function () {
    session()->forget(['reservation', 'reservation_expires_at']);

    return redirect('/')->withErrors([
        'reservation' => 'Rezervasyon süreniz doldu. İşleminiz iptal edildi, lütfen aramanızı yeniden yapın.',
    ]);
})->name('reservation.timeout');

// Yalnızca yerel geliştirme: e-posta şablonunu tarayıcıda önizler.
// Mailable döndürüldüğünde Laravel onu doğru içerik tipiyle render eder.
if (app()->isLocal()) {
    Route::get('/mail-onizleme/{pnr}', function (string $pnr) {
        $tickets = \App\Models\Ticket::with([
            'passenger',
            'flight.route.originAirport',
            'flight.route.destinationAirport',
            'flight.aircraft',
        ])
            ->where('pnr', $pnr)
            ->get();

        abort_if($tickets->isEmpty(), 404, 'Bu PNR bulunamadı.');

        return new \App\Mail\ReservationConfirmed($pnr, $tickets, $tickets->sum('final_price'));
    });
}
