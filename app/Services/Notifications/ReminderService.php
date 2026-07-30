<?php

namespace App\Services\Notifications;

use App\Models\Flight;
use App\Models\TicketReminder;
use App\Notifications\ReminderType;
use Illuminate\Support\Collection;

/**
 * Hatırlatma kayıtlarını planlar, günceller ve iptal eder.
 *
 * Tasarım kararı: hatırlatmalar kuyruğa gecikmeli iş olarak atılmıyor,
 * veritabanına yazılıyor. Sebep — kuyruk kalıcı kayıt yeri değildir. Aylar
 * sonrası için kuyrukta bekleyen bir iş, Redis yeniden başlarsa ya da kuyruk
 * temizlenirse sessizce kaybolur ve geri getirmenin yolu olmaz. Veritabanı
 * kaydı ise her taramada yeniden bulunur.
 */
class ReminderService
{
    /**
     * Bir rezervasyonun tüm bacakları için hatırlatmaları planlar.
     *
     * @param Collection $tickets İlişkileri yüklenmiş biletler (flight gerekli)
     */
    public function scheduleForReservation(string $pnr, Collection $tickets): void
    {
        foreach ($tickets->groupBy('flight_id') as $group) {
            $flight = $group->first()->flight;

            foreach (ReminderType::cases() as $type) {
                $this->schedule($pnr, $flight, $type);
            }
        }
    }

    /** Tek bir hatırlatma kaydı oluşturur. */
    public function schedule(string $pnr, Flight $flight, ReminderType $type): ?TicketReminder
    {
        $at = $flight->departure_time->copy()->subHours($type->hoursBefore());

        // Zamanı çoktan geçmiş hatırlatma oluşturulmaz: rezervasyon onayı
        // aynı anda gidiyor ve check-in bağlantısını zaten içeriyor,
        // saniyeler arayla ikinci mesaj göndermek spam olur.
        if ($at->isPast()) {
            return null;
        }

        return TicketReminder::updateOrCreate(
            ['pnr' => $pnr, 'flight_id' => $flight->id, 'type' => $type->value],
            ['scheduled_at' => $at, 'status' => 'pending']
        );
    }

    /**
     * Uçuş saati değiştiğinde bekleyen hatırlatmaları yeni saate taşır.
     *
     * Gecikmeli iş yaklaşımında bu mümkün olmazdı — kuyruğa atılmış işi
     * bulup silmek gerekirdi. Burada tek UPDATE yeterli.
     */
    public function rescheduleForFlight(Flight $flight): void
    {
        $reminders = TicketReminder::where('flight_id', $flight->id)
            ->where('status', 'pending')
            ->get();

        foreach ($reminders as $reminder) {
            $at = $flight->departure_time->copy()->subHours($reminder->type->hoursBefore());

            $reminder->update([
                'scheduled_at' => $at,
                'status'       => $at->isPast() ? 'cancelled' : 'pending',
            ]);
        }
    }

    /** Rezervasyon iptal edildiğinde bekleyen hatırlatmaları durdurur. */
    public function cancelForReservation(string $pnr): int
    {
        return TicketReminder::where('pnr', $pnr)
            ->whereIn('status', ['pending', 'queued'])
            ->update(['status' => 'cancelled']);
    }
}
