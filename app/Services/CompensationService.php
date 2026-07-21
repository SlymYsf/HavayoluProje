<?php

namespace App\Services;

use App\Models\Compensation;
use App\Models\Flight;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class CompensationService
{
    /**
     * Bir uçuşun mevcut kapasitesine göre "açıkta kalan" (fazla) yolcuları tespit eder
     * ve her biri için otomatik tazminat kaydı oluşturur.
     *
     * Ne zaman kullanılır: uçak değişikliği (küçültme) veya iptal sonrası,
     * satılmış bilet sayısı yeni kapasiteyi aştığında.
     */
    public function processOverbookedPassengers(Flight $flight, string $reason): Collection
    {
        $flight = $flight->fresh(['aircraft']); // çağıranın elindeki obje bayat olabilir, her zaman güncel veriyi çek

        $maxSellable = (int) floor($flight->aircraft->total_capacity * 1.10);

        $excessCount = $flight->sold_seats - $maxSellable;

        if ($excessCount <= 0) {
            return collect();
        }

        $affectedTickets = Ticket::where('flight_id', $flight->id)
            ->where('status', 'confirmed')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take($excessCount)
            ->get();

        return $affectedTickets->map(function (Ticket $ticket) use ($reason) {
            $ticket->update(['status' => 'compensated']);

            return Compensation::create([
                'ticket_id'            => $ticket->id,
                'reason'               => $reason,
                'compensation_amount'  => $this->calculateCompensationAmount($ticket),
            ]);
        });
    }

    /** Basit kural: bilet fiyatının 1.5 katı tazminat (mağduriyet cezası niyetine). */
    private function calculateCompensationAmount(Ticket $ticket): int
    {
        return (int) round($ticket->final_price * 1.5);
    }
}
