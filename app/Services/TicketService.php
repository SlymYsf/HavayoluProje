<?php

namespace App\Services;

use App\Models\Flight;
use App\Models\Passenger;
use App\Models\Ticket;
use App\Services\Pricing\PricingService;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /** Kapasitenin %10 fazlasına kadar satışa izin verilir (overbooking). */
    private const OVERBOOKING_MULTIPLIER = 1.10;

    public function __construct(
        private FlightService $flightService,
        private PricingService $pricingService,
    ) {}

    /**
     * Bir uçuş için bilet satın alır. Overbooking limiti dahilinde satışa izin verir.
     * Kapasite (overbooking dahil) doluysa exception fırlatır.
     *
     * @param array{first_name: string, last_name: string, email: string, tc_or_passport_no?: string, phone?: string} $passengerData
     */
    public function purchaseTicket(Flight $flight, string $cabinClass, array $passengerData): Ticket
    {
        return DB::transaction(function () use ($flight, $cabinClass, $passengerData) {
            $lockedFlight = Flight::where('id', $flight->id)->lockForUpdate()->first();

            $sellableClasses = $this->flightService->getSellableCabinClasses($lockedFlight->aircraft, $lockedFlight->route);

            if (! in_array($cabinClass, $sellableClasses, true)) {
                throw new \InvalidArgumentException("'{$cabinClass}' sınıfı bu uçuşta satışa açık değil.");
            }

            $maxSellable = (int) floor($lockedFlight->aircraft->total_capacity * self::OVERBOOKING_MULTIPLIER);

            if ($lockedFlight->sold_seats >= $maxSellable) {
                throw new \RuntimeException("Uçuş dolu (overbooking limiti dahil): {$lockedFlight->flight_number}");
            }

            $passenger = Passenger::firstOrCreate(
                ['email' => $passengerData['email']],
                $passengerData
            );

            $price = $this->pricingService->calculatePrice($lockedFlight, $cabinClass);

            $ticket = Ticket::create([
                'pnr'          => $this->generatePnr(),
                'flight_id'    => $lockedFlight->id,
                'passenger_id' => $passenger->id,
                'cabin_class'  => $cabinClass,
                'seat_number'  => $this->assignSeatNumber($lockedFlight, $cabinClass),
                'final_price'  => $price,
                'status'       => 'confirmed',
            ]);

            $lockedFlight->increment('sold_seats');

            return $ticket;
        });
    }

    private function generatePnr(): string
    {
        do {
            $code = 'DH-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5));
        } while (Ticket::where('pnr', $code)->exists());

        return $code;
    }

    private function assignSeatNumber(Flight $flight, string $cabinClass): string
    {
        $aircraft = $flight->aircraft;

        // Kabin sınıfına göre koltuk bloğunun sınırlarını belirle (business önde, economy arkada)
        [$rowStart, $rowEnd] = match ($cabinClass) {
            'business'        => [1, (int) ceil($aircraft->business_seats / 6)],
            'premium_economy' => [
                (int) ceil($aircraft->business_seats / 6) + 1,
                (int) ceil(($aircraft->business_seats + $aircraft->premium_economy_seats) / 6),
            ],
            'economy' => [
                (int) ceil(($aircraft->business_seats + $aircraft->premium_economy_seats) / 6) + 1,
                (int) ceil($aircraft->total_capacity / 6),
            ],
            default => throw new \InvalidArgumentException("Bilinmeyen kabin sınıfı: {$cabinClass}"),
        };

        $takenSeats = Ticket::where('flight_id', $flight->id)
            ->whereNotNull('seat_number')
            ->pluck('seat_number')
            ->toArray();

        $letters = ['A', 'B', 'C', 'D', 'E', 'F'];

        do {
            $row = random_int($rowStart, $rowEnd);
            $seat = $row . $letters[array_rand($letters)];
        } while (in_array($seat, $takenSeats, true));

        return $seat;
    }
    public function findByPnrAndSurname(string $pnr, string $lastName): ?Ticket
    {
        return Ticket::whereHas('passenger', fn ($q) => $q->where('last_name', $lastName))
            ->where('pnr', $pnr)
            ->first();
    }

    public function checkIn(Ticket $ticket): Ticket
    {
        if ($ticket->status !== 'confirmed') {
            throw new \InvalidArgumentException('Sadece onaylı biletler check-in yapabilir.');
        }

        if ($ticket->checked_in_at !== null) {
            throw new \RuntimeException('Bu bilet zaten check-in yapılmış.');
        }

        $flight = $ticket->flight;

        if (! in_array($flight->status, ['Planlandı', 'Gecikmeli'], true)) {
            throw new \RuntimeException("Bu uçuş için check-in yapılamaz (durum: {$flight->status}).");
        }

        if ($flight->departure_time->isPast()) {
            throw new \RuntimeException('Bu uçuşun kalkış saati geçti, check-in yapılamaz.');
        }

        $ticket->checked_in_at = now();
        $ticket->save();

        return $ticket;
    }

    public function cancelTicket(Ticket $ticket): Ticket
    {
        return DB::transaction(function () use ($ticket) {
            if ($ticket->status !== 'confirmed') {
                throw new \InvalidArgumentException('Sadece onaylı biletler iptal edilebilir.');
            }

            $flight = Flight::where('id', $ticket->flight_id)->lockForUpdate()->first();

            if ($flight->departure_time->isPast()) {
                throw new \RuntimeException('Kalkışı geçmiş bir uçuşun bileti iptal edilemez.');
            }

            $ticket->status = 'cancelled';
            $ticket->save();

            $flight->decrement('sold_seats');

            return $ticket;
        });
    }
}
