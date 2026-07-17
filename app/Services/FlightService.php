<?php

namespace App\Services;

use App\Models\Aircraft;
use App\Models\Flight;
use App\Models\Route;

class FlightService
{
    private const HUB_ROUTE_CODES = ['ESB', 'ADB', 'AYT'];

    private const ALLOWED_STATUS_TRANSITIONS = [
        'Planlandı'  => ['Gecikmeli', 'İptal', 'Tamamlandı'],
        'Gecikmeli'  => ['İptal', 'Tamamlandı'],
        'İptal'      => [],
        'Tamamlandı' => [],
    ];

    public function canAssignAircraft(Aircraft $aircraft, Route $route): bool
    {
        if ($aircraft->body_type === 'wide' && $route->route_type === 'domestic') {
            $otherAirport = $route->originAirport->is_hub
                ? $route->destinationAirport
                : $route->originAirport;

            return in_array($otherAirport->iata_code, self::HUB_ROUTE_CODES);
        }

        return true;
    }

    public function getSellableCabinClasses(Aircraft $aircraft, Route $route): array
    {
        if ($aircraft->body_type === 'narrow' && $route->route_type === 'domestic') {
            return ['economy'];
        }

        $classes = ['economy'];

        if ($aircraft->business_seats > 0) {
            $classes[] = 'business';
        }

        if ($aircraft->premium_economy_seats > 0) {
            $classes[] = 'premium_economy';
        }

        return $classes;
    }

    /**
     * Verilen rota için uygun (kurallara uyan) bir uçak rastgele seçip yeni bir uçuş oluşturur.
     * Uçağın o saatte müsait olup olmadığını KONTROL ETMEZ — bu proje kapsamı dışında
     * bırakıldı (bkz. PROJECT_CONTEXT.md, "Gün 7 İçin Ayrı Bir Karar").
     */
    public function createFlight(Route $route, \DateTimeInterface $departureTime, \DateTimeInterface $arrivalTime): Flight
    {
        $aircraft = $this->pickRandomEligibleAircraft($route);

        if (! $aircraft) {
            throw new \RuntimeException("Bu rota için uygun uçak bulunamadı (route_id: {$route->id}).");
        }

        return Flight::create([
            'flight_number'  => $this->generateFlightNumber(),
            'route_id'       => $route->id,
            'aircraft_id'    => $aircraft->id,
            'departure_time' => $departureTime,
            'arrival_time'   => $arrivalTime,
            'status'         => 'Planlandı',
            'sold_seats'     => 0,
        ]);
    }

    /**
     * Bir uçuşun statüsünü değiştirir, sadece izin verilen geçişlere göre.
     * Geçersiz bir geçiş denenirse (örn. İptal → Planlandı) exception fırlatır.
     */
    public function changeStatus(Flight $flight, string $newStatus): Flight
    {
        $allowed = self::ALLOWED_STATUS_TRANSITIONS[$flight->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Geçersiz durum geçişi: {$flight->status} → {$newStatus}"
            );
        }

        $flight->status = $newStatus;
        $flight->save();

        return $flight;
    }

    private function pickRandomEligibleAircraft(Route $route): ?Aircraft
    {
        $eligible = Aircraft::all()->filter(
            fn (Aircraft $aircraft) => $this->canAssignAircraft($aircraft, $route)
        );

        return $eligible->isEmpty() ? null : $eligible->random();
    }

    private function generateFlightNumber(): string
    {
        do {
            $number = 'DH' . random_int(100, 9999);
        } while (Flight::where('flight_number', $number)->exists());

        return $number;
    }
}
