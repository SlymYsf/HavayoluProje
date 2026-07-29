<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rezervasyon oturumunun süresi dolmuşsa akışı keser.
 *
 * Süre, yolcu bilgileri sayfasına ilk girişte ReservationController tarafından
 * session'a yazılır. Bu kontrol frontend sayacından bağımsızdır — kullanıcı
 * sekmeyi açık bırakıp saatler sonra form gönderirse burada durdurulur.
 */
class ReservationTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        $expiresAt = session('reservation_expires_at');

        if (! $expiresAt || Carbon::parse($expiresAt)->isPast()) {
            session()->forget(['reservation', 'reservation_expires_at']);

            return redirect('/')->withErrors([
                'reservation' => 'Rezervasyon süreniz doldu. Lütfen aramanızı yeniden yapın.',
            ]);
        }

        return $next($request);
    }
}
