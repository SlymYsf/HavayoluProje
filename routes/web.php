<?php

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CountryCodeController;
use App\Http\Controllers\FareCalendarController;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\FlightStatusController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SeatMapController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketManagementController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FleetController;

/* ===== SAYFALAR ===== */

Route::get('/fleet', [\App\Http\Controllers\FleetController::class, 'index'])->name('static.fleet');
Route::get('/yesilkoy-havalimani', fn () => view('static.yesilkoy'))->name('static.yesilkoy');
Route::get('/filo', [FleetController::class, 'index'])->name('static.fleet');
Route::get('/kabin-siniflari/{class?}', [\App\Http\Controllers\CabinClassController::class, 'show'])
    ->name('static.cabin');
Route::get('/uye-ol', fn () => view('auth.register'))->name('auth.register');
Route::get('/', [FlightSearchController::class, 'index']);
Route::get('/ucus-durumu', fn () => view('flights.status'))->name('flights.status');
Route::get('/ucus-sonuclari', fn () => view('flights.results'))->name('flights.results');
Route::get('/check-in', fn () => view('flights.checkin'))->name('flights.checkin');
Route::get('/bilet-yonetimi', fn () => view('flights.manage'))->name('flights.manage');
/* ===== API ===== */

Route::get('/api/airports', [FlightSearchController::class, 'airports']);
Route::get('/api/airports/{airport}/destinations', [FlightSearchController::class, 'destinations']);
Route::get('/api/airports/{airport}/origins', [FlightSearchController::class, 'origins']);
Route::get('/api/flights/search', [FlightSearchController::class, 'search']);
Route::get('/api/flights/status', [FlightStatusController::class, 'show']);
Route::get('/api/flights/{flight}/seat-map', [SeatMapController::class, 'show']);
Route::get('/api/fares/calendar', [FareCalendarController::class, 'strip']);
Route::get('/api/country-codes', [CountryCodeController::class, 'index']);
Route::get('/api/announcements', [AnnouncementController::class, 'index']);
Route::post('/api/tickets', [TicketController::class, 'store']);
Route::post('/api/checkin', [CheckInController::class, 'store']);
Route::get('/api/tickets/manage', [TicketManagementController::class, 'show']);
Route::post('/api/tickets/cancel', [TicketManagementController::class, 'cancel']);
Route::post('/dil', [LocaleController::class, 'update'])->name('locale.update');

/* ===== REZERVASYON AKIŞI ===== */

// Bu sayfa sayacı BAŞLATIR, o yüzden middleware yok
Route::get('/yolcu-bilgileri', [ReservationController::class, 'passengers'])
    ->name('reservation.passengers');

// Bundan sonraki her adım süre kontrolünden geçer
Route::middleware('reservation.timeout')->group(function () {
    Route::post('/rezervasyon/yolcular', [ReservationController::class, 'storePassengers'])
        ->name('reservation.passengers.store');

    Route::get('/koltuk-secimi', [ReservationController::class, 'seats'])
        ->name('reservation.seats');

    Route::post('/rezervasyon/koltuklar', [ReservationController::class, 'storeSeats'])
        ->name('reservation.seats.store');

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
// Yalnızca yerel geliştirme: e-posta şablonlarını tarayıcıda önizler.
// Mailable döndürüldüğünde Laravel onu doğru içerik tipiyle render eder.
if (app()->isLocal()) {

    // Rezervasyon onayı
    Route::get('/mail-onizleme/rezervasyon/{pnr}', function (string $pnr) {
        $tickets = \App\Models\Ticket::with([
            'passenger',
            'flight.route.originAirport',
            'flight.route.destinationAirport',
            'flight.aircraft',
        ])
            ->where('pnr', $pnr)
            ->get();

        abort_if($tickets->isEmpty(), 404, 'Bu PNR bulunamadı.');

        // Ödenen toplam = bilet ücreti + koltuk ücreti
        $total = $tickets->sum(fn ($t) => (float) $t->final_price + (float) $t->seat_fee);

        return new \App\Mail\ReservationConfirmed($pnr, $tickets, $total);
    });

    // Biniş kartı
    Route::get('/mail-onizleme/binis-karti/{pnr}', function (string $pnr) {
        $tickets = \App\Models\Ticket::with([
            'passenger',
            'flight.route.originAirport',
            'flight.route.destinationAirport',
            'flight.aircraft',
        ])
            ->where('pnr', $pnr)
            ->get();

        abort_if($tickets->isEmpty(), 404, 'Bu PNR bulunamadı.');

        return new \App\Mail\BoardingPassIssued($pnr, $tickets->groupBy('flight_id')->first());
    });

    // Check-in hatırlatması
    Route::get('/mail-onizleme/hatirlatma/{pnr}', function (string $pnr) {
        $tickets = \App\Models\Ticket::with([
            'passenger',
            'flight.route.originAirport',
            'flight.route.destinationAirport',
        ])
            ->where('pnr', $pnr)
            ->get();

        abort_if($tickets->isEmpty(), 404, 'Bu PNR bulunamadı.');

        $leg = $tickets->groupBy('flight_id')->first();
        $url = url('/check-in') . '?pnr=' . urlencode($pnr)
            . '&last_name=' . urlencode($leg->first()->passenger->last_name);

        return new \App\Mail\CheckInReminder($pnr, $leg, $url);
    });


}
