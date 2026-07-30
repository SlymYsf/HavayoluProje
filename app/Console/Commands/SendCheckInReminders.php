<?php

namespace App\Console\Commands;

use App\Jobs\SendReservationNotification;
use App\Models\Ticket;
use App\Models\TicketReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Zamanı gelen hatırlatma kayıtlarını bulup kuyruğa atar. Gönderim YAPMAZ.
 *
 * Tarama artık bilet–uçuş birleştirmesi yapmıyor; tek tablodan indeksli
 * bir sorgu çekiyor (status + scheduled_at). Binlerce kayıtta bile
 * milisaniyeler sürüyor.
 */
class SendCheckInReminders extends Command
{
    protected $signature = 'reminders:dispatch {--dry-run : Kuyruğa iş atmadan neyin gideceğini listeler}';

    protected $description = 'Zamanı gelen hatırlatmaları kuyruğa atar';

    private const CHUNK = 500;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $dispatched = 0;

        TicketReminder::with('flight.route.originAirport', 'flight.route.destinationAirport')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(self::CHUNK, function (Collection $reminders) use ($dryRun, &$dispatched) {
                foreach ($reminders as $reminder) {
                    $ticketIds = $this->ticketIdsFor($reminder);

                    // Bilet kalmamışsa hatırlatmanın anlamı yok
                    if (empty($ticketIds)) {
                        if (! $dryRun) {
                            $reminder->update(['status' => 'cancelled']);
                        }
                        continue;
                    }

                    if ($dryRun) {
                        $this->line($this->describe($reminder, count($ticketIds)));
                        $dispatched++;
                        continue;
                    }

                    SendReservationNotification::dispatch(
                        $reminder->type->notification(),
                        $reminder->pnr,
                        $ticketIds,
                        $reminder->id
                    );

                    // Kuyruğa atıldığı anda işaretleniyor: komut 15 dakikada bir
                    // çalıştığı için iş henüz işlenmemişken mükerrer atım olmasın.
                    $reminder->update([
                        'status'    => 'queued',
                        'queued_at' => now(),
                        'attempts'  => $reminder->attempts + 1,
                    ]);

                    $dispatched++;
                }
            });

        if ($dispatched === 0) {
            $this->info('Gönderilecek hatırlatma yok.');
            return self::SUCCESS;
        }

        $this->info($dryRun
            ? "Kuru çalışma — {$dispatched} bildirim kuyruğa atılacaktı."
            : "{$dispatched} hatırlatma kuyruğa atıldı.");

        return self::SUCCESS;
    }

    /** Hatırlatmanın kapsadığı, hâlâ geçerli biletler. */
    private function ticketIdsFor(TicketReminder $reminder): array
    {
        return Ticket::where('pnr', $reminder->pnr)
            ->where('flight_id', $reminder->flight_id)
            ->where('status', 'confirmed')
            ->pluck('id')
            ->all();
    }

    private function describe(TicketReminder $reminder, int $count): string
    {
        return sprintf(
            '  %s · %s · %s · %d yolcu · planlanan: %s',
            $reminder->pnr,
            $reminder->flight->flight_number,
            $reminder->type->value,
            $count,
            $reminder->scheduled_at->format('d.m.Y H:i')
        );
    }
}
