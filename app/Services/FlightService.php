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

    /**
     * Gün içi saat dilimleri. Sunucu tarafı tek doğru kaynak; istemci
     * yalnızca anahtar gönderiyor.
     */
    private const TIME_SLOTS = [
        'midnight'  => ['00:00:00', '06:00:00'],
        'morning'   => ['06:00:00', '10:00:00'],
        'noon'      => ['10:00:00', '14:00:00'],
        'afternoon' => ['14:00:00', '18:00:00'],
        'evening'   => ['18:00:00', '22:00:00'],
        'night'     => ['22:00:00', '23:59:59'],
    ];

    public function canAssignAircraft(Aircraft $aircraft, Route $route): bool
    {
        // Geniş gövde + iç hat → sadece belirlenmiş hub varışlarına
        if ($aircraft->body_type === 'wide' && $route->route_type === 'domestic') {
            $otherAirport = $route->originAirport->is_hub
                ? $route->destinationAirport
                : $route->originAirport;

            return in_array($otherAirport->iata_code, self::HUB_ROUTE_CODES);
        }

        // Dar gövde → menzili uzun/ultra-uzun dış hatlara yetmez
        // (B737-800: 5.765 km, A321neo: 6.500 km — IST-JFK bile ~8.100 km)
        if ($aircraft->body_type === 'narrow') {
            $category = $route->getRangeCategory();
            if (in_array($category, ['long', 'ultra_long'], true)) {
                return false;
            }
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
    public function createFlight(Route $route, \DateTimeInterface $departureTime, \DateTimeInterface $arrivalTime, ?string $flightNumber = null): Flight
    {
        $aircraft = $this->pickRandomEligibleAircraft($route);

        if (! $aircraft) {
            throw new \RuntimeException("Bu rota için uygun uçak bulunamadı (route_id: {$route->id}).");
        }

        return Flight::create([
            'flight_number'  => $flightNumber ?? $this->generateFlightNumber(),
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
            $number = 'DH' . random_int(100, 999999);
        } while (Flight::where('flight_number', $number)->exists());

        return $number;
    }

    // ===== UÇUŞ DURUMU SORGULARI =====

    public function findByNumberAndDate(string $flightNumber, string $date): ?Flight
    {
        return Flight::where('flight_number', $flightNumber)
            ->whereDate('departure_time', $date)
            ->first();
    }

    /**
     * Bir veya birden çok havalimanından belirli gün kalkan uçuşlar.
     *
     * Şehir seçildiğinde birden çok havalimanı kimliği gelir (Londra →
     * LHR, LGW, STN). Hub havalimanlarında günde yüzlerce uçuş olabildiği
     * için sonuç sınırlanıyor; bu ekran zaman çizelgesi değil, sorgu aracı.
     *
     * @param int[] $airportIds
     */
    public function findDeparturesFrom(array $airportIds, string $date, ?string $timeSlot = null, int $limit = 50)
    {
        $query = Flight::with(['route.originAirport', 'route.destinationAirport', 'aircraft'])
            ->whereDate('departure_time', $date)
            ->whereHas('route', fn ($q) => $q->whereIn('origin_airport_id', $airportIds));

        $this->applyTimeSlot($query, 'departure_time', $timeSlot);

        return $query->orderBy('departure_time')->limit($limit)->get();
    }

    /**
     * Bir veya birden çok havalimanına belirli gün inen uçuşlar.
     *
     * @param int[] $airportIds
     */
    public function findArrivalsTo(array $airportIds, string $date, ?string $timeSlot = null, int $limit = 50)
    {
        $query = Flight::with(['route.originAirport', 'route.destinationAirport', 'aircraft'])
            ->whereDate('arrival_time', $date)
            ->whereHas('route', fn ($q) => $q->whereIn('destination_airport_id', $airportIds));

        $this->applyTimeSlot($query, 'arrival_time', $timeSlot);

        return $query->orderBy('arrival_time')->limit($limit)->get();
    }

    /**
     * İki nokta arasındaki belirli gün uçuşları.
     *
     * @param int[] $originIds
     * @param int[] $destinationIds
     */
    public function findByRoute(array $originIds, array $destinationIds, string $date, int $limit = 50)
    {
        return Flight::with(['route.originAirport', 'route.destinationAirport', 'aircraft'])
            ->whereDate('departure_time', $date)
            ->whereHas('route', function ($q) use ($originIds, $destinationIds) {
                $q->whereIn('origin_airport_id', $originIds)
                    ->whereIn('destination_airport_id', $destinationIds);
            })
            ->orderBy('departure_time')
            ->limit($limit)
            ->get();
    }

    /** Bilinmeyen dilim anahtarı sessizce yok sayılır — filtre uygulanmaz. */
    private function applyTimeSlot($query, string $column, ?string $slot): void
    {
        if (! $slot || ! isset(self::TIME_SLOTS[$slot])) {
            return;
        }

        [$start, $end] = self::TIME_SLOTS[$slot];

        $query->whereTime($column, '>=', $start)
            ->whereTime($column, '<=', $end);
    }

    /** İstemciye açık dilim anahtarları — doğrulama için. */
    public static function timeSlotKeys(): array
    {
        return array_keys(self::TIME_SLOTS);
    }
}
