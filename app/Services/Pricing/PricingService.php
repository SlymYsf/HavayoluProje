<?php

namespace App\Services\Pricing;

use App\Models\Flight;

class PricingService
{
    /**
     * Yolcu tipi çarpanları (23 Temmuz 2026'da eklendi).
     *
     * Bu oranlar genel havayolu pratiğine dayanan mühendislik kararıdır,
     * belirli bir kaynaktan alınmamıştır:
     *  - Bebek koltuk işgal etmez (kucakta seyahat), sembolik ücret alınır
     *  - Çocuk ve öğrenci indirimleri sektörde yaygın uygulamalardır
     */
    private const PASSENGER_TYPE_MULTIPLIERS = [
        'adult'   => 1.00,
        'student' => 0.85,
        'child'   => 0.75,
        'infant'  => 0.10,
    ];

    public function __construct(private PricingStrategyFactory $factory) {}

    public function calculatePrice(Flight $flight, string $cabinClass, string $passengerType = 'adult'): int
    {
        $basePrice = $this->factory
            ->resolve($flight, $cabinClass)
            ->calculatePrice($flight, $cabinClass);

        return (int) round($basePrice * $this->getPassengerTypeMultiplier($passengerType));
    }

    public function getPassengerTypeMultiplier(string $passengerType): float
    {
        if (! isset(self::PASSENGER_TYPE_MULTIPLIERS[$passengerType])) {
            throw new \InvalidArgumentException("Bilinmeyen yolcu tipi: {$passengerType}");
        }

        return self::PASSENGER_TYPE_MULTIPLIERS[$passengerType];
    }

    /** Bebekler koltuk işgal etmez — kapasite hesabında sayılmaz. */
    public function occupiesSeat(string $passengerType): bool
    {
        return $passengerType !== 'infant';
    }
}
