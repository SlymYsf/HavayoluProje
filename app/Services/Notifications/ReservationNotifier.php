<?php

namespace App\Services\Notifications;

use App\Mail\BoardingPassIssued;
use App\Mail\CheckInReminder;
use App\Mail\ReservationConfirmed;
use App\Models\Ticket;
use App\Notifications\NotificationType;
use App\Support\TurkishAscii;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use App\Mail\ReservationCancelled;

/**
 * Bildirim içeriğini üretir — göndermez.
 *
 * Mail sınıfı seçimi ve SMS metni burada; gönderim App\Jobs\SendReservationNotification
 * içinde yapılıyor. Bu ayrım sayesinde metinleri değiştirmek için kuyruk
 * mantığına dokunmak gerekmiyor.
 */
class ReservationNotifier
{
    /**
     * SMS metinleri ASCII yazılır.
     *
     * Ölçüm: aynı 86 karakterlik mesaj Türkçe karakterlerle UCS-2 moduna
     * geçip 2 segment, ASCII ile GSM-7 kalıp 1 segment tüketiyor. Segment
     * başına ücretlendirme olduğu için maliyet iki katına çıkıyor.
     */
    private const SMS_ASCII = true;

    public function mailable(NotificationType $type, string $pnr, Collection $tickets): ?Mailable
    {
        return match ($type) {
            NotificationType::ReservationConfirmed => new ReservationConfirmed(
                $pnr,
                $tickets,
                $tickets->sum('final_price')
            ),
            NotificationType::ReservationCancelled => new ReservationCancelled(
                $pnr,
                $tickets,
                $tickets->sum('final_price')
            ),
            NotificationType::BoardingPass    => new BoardingPassIssued($pnr, $tickets),
            NotificationType::CheckInReminder => new CheckInReminder(
                $pnr,
                $tickets,
                $this->checkInUrl($pnr, $tickets)
            ),
        };
    }

    public function smsText(NotificationType $type, string $pnr, Collection $tickets): string
    {
        /** @var Ticket $first */
        $first  = $tickets->first();
        $flight = $first->flight;

        $lastName = $first->passenger->last_name;
        $date     = $flight->departure_time->format('d.m.Y');
        $route    = $flight->route->originAirport->iata_code . '-' . $flight->route->destinationAirport->iata_code;

        // Sade adres: sorgu parametreleri 40+ karakter ekleyip mesajı ikinci
        // segmente taşıyor, taşıdıkları bilgi (PNR, soyad) metinde zaten var.
        $shortUrl = preg_replace('#^https?://#', '', url('/check-in'));

        $text = match ($type) {
            NotificationType::ReservationConfirmed => sprintf(
                'Sn. %s, %s kodlu rezervasyonunuz olusturuldu. %s %s %s. Detay: %s',
                $lastName, $pnr, $flight->flight_number, $date, $route, $shortUrl
            ),
            NotificationType::ReservationCancelled => sprintf(
                'Sn. %s, %s kodlu rezervasyonunuz iptal edildi. Iade islemi 3-5 is gunu icinde tamamlanir.',
                $lastName, $pnr
            ),

            NotificationType::BoardingPass => sprintf(
                'Sn. %s, %s %s %s ucusu check-in islemi tamamlandi. Koltuk: %s PNR: %s',
                $lastName, $flight->flight_number, $date, $route,
                $this->seatList($tickets), $pnr
            ),

            NotificationType::CheckInReminder => sprintf(
                'Sn. %s, %s %s %s ucusunuz check-in islemine acildi. PNR: %s Detay: %s',
                $lastName, $flight->flight_number, $date, $route, $pnr, $shortUrl
            ),
        };

        return self::SMS_ASCII ? TurkishAscii::convert($text) : $text;
    }

    /** Koltuk numaraları; bebeklerde koltuk olmadığı için atlanır. */
    private function seatList(Collection $tickets): string
    {
        $seats = $tickets->pluck('seat_number')->filter()->all();

        return empty($seats) ? '-' : implode(', ', $seats);
    }

    private function checkInUrl(string $pnr, Collection $tickets): string
    {
        return url('/check-in')
            . '?pnr=' . urlencode($pnr)
            . '&last_name=' . urlencode($tickets->first()->passenger->last_name);
    }
}
