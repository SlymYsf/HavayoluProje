<?php

namespace App\Services\Market;

use App\Models\MarketRate;
use Illuminate\Support\Facades\Cache;

/**
 * Piyasa koşullarının bilet fiyatına yansıması.
 *
 * Model: maliyet aktarımı. Bilet fiyatının bir kısmı yakıta, bir kısmı
 * dövize bağlı giderlerden (uçak kirası, bakım, sigorta), kalanı TL
 * cinsinden giderlerden (personel, yer hizmetleri) oluşur. Yakıt payı uzun
 * uçuşlarda daha yüksektir; ağırlıklar bu yüzden menzile göre değişir.
 *
 *   yakıtTLOranı = (yakıt_şimdi × kur_şimdi) / (yakıt_ref × kur_ref)
 *   kurOranı     = kur_şimdi / kur_ref
 *   çarpan       = sabitPay + yakıtAğırlık × yakıtTLOranı + kurAğırlık × kurOranı
 *
 * API'ye fiyat hesabı sırasında ASLA gidilmez: veriyi `market:sync` komutu
 * günde bir kez çeker, buradaki okuma önbellekten yapılır. Yedek zinciri
 * önbellek -> veritabanı -> çarpan 1,00. İnternet olmasa da fiyatlandırma
 * çalışmaya devam eder.
 *
 * Önbelleğe Eloquent modeli DEĞİL düz dizi konuyor: sürücü `database` olduğu
 * için model serileştirilip tabloya yazılıyor ve geri okunurken
 * __PHP_Incomplete_Class'a dönüşüyordu. Ayrıca modelin bağlantı ve ilişki
 * durumunu önbellekte taşımak zaten doğru değil.
 */
class MarketRateService
{
    private const CACHE_KEY = 'market_rate:latest';

    /** Uçuş menziline göre çarpan. */
    /** Uçuş menziline göre çarpan. */
    public function multiplier(string $rangeCategory, ?array $snapshot = null): float
    {
        $current   = $snapshot ?? $this->snapshot();
        $reference = $this->reference();

        if ($reference === null || $current === null) {
            return 1.0;
        }

        if ($reference['usd_try'] <= 0 || $reference['jet_fuel_usd'] <= 0) {
            return 1.0;
        }

        $weights = config("pricing_market.weights.{$rangeCategory}")
            ?? config('pricing_market.weights.medium');

        $fuelWeight = (float) $weights['fuel'];
        $fxWeight   = (float) $weights['fx'];
        $cpiWeight  = (float) ($weights['cpi'] ?? 0.0);

        $fxRatio = $current['usd_try'] / $reference['usd_try'];

        $fuelTryRatio = ($current['jet_fuel_usd'] * $current['usd_try'])
            / ($reference['jet_fuel_usd'] * $reference['usd_try']);

        // TÜFE verisi yoksa bu pay hareketsiz sayılır (oran 1,00) — katman
        // devre dışı kalır ama fiyatlandırma çalışmaya devam eder.
        $cpiRatio = $this->cpiRatio($current);

        $multiplier = ($fuelWeight * $fuelTryRatio)
            + ($fxWeight * $fxRatio)
            + ($cpiWeight * $cpiRatio);

        // Kaynak bozuk veri dönerse fiyat uçmasın ya da çökmesin
        return max(
            (float) config('pricing_market.bounds.min', 0.75),
            min((float) config('pricing_market.bounds.max', 2.50), $multiplier)
        );
    }

    /** Güncel TÜFE endeksinin referans aya oranı; veri eksikse 1,00. */
    private function cpiRatio(array $current): float
    {
        $referenceCpi = $this->cpiReference();
        $currentCpi   = $current['cpi'] ?? null;

        if ($referenceCpi === null || $currentCpi === null || $referenceCpi <= 0) {
            return 1.0;
        }

        return $currentCpi / $referenceCpi;
    }

    /** base_price yazılırken bilinen son TÜFE endeksi. */
    public function cpiReference(): ?float
    {
        $month = config('pricing_market.cpi_reference_month');

        return Cache::remember(
            'market_rate:cpi_reference:' . $month,
            now()->addDay(),
            function () use ($month) {
                $rate = MarketRate::whereDate('rate_date', $month)->first();

                return $rate?->cpi !== null ? (float) $rate->cpi : null;
            }
        );
    }

    /**
     * Rezervasyon boyunca sabit kalacak piyasa görüntüsü.
     * Session'a yazılır: kullanıcı kart bilgisi girerken önbellek yenilenip
     * tutar değişmesin.
     *
     * @return array{usd_try: float, jet_fuel_usd: float, rate_date: string}|null
     */
    public function snapshot(): ?array
    {
        $ttl = (int) config('pricing_market.cache_ttl_minutes', 720);

        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes($ttl),
            fn () => $this->toArray(MarketRate::orderByDesc('rate_date')->first())
        );
    }

    /**
     * base_price değerlerinin tanımlandığı günün piyasa verisi.
     *
     * @return array{usd_try: float, jet_fuel_usd: float, rate_date: string}|null
     */
    public function reference(): ?array
    {
        $date = config('pricing_market.reference_date');

        return Cache::remember(
            'market_rate:reference:' . $date,
            now()->addDay(),
            fn () => $this->toArray(MarketRate::whereDate('rate_date', $date)->first())
        );
    }

    public function forgetCache(): void
    {
        Cache::forget('market_rate:cpi_reference:' . config('pricing_market.cpi_reference_month'));
        Cache::forget(self::CACHE_KEY);
        Cache::forget('market_rate:reference:' . config('pricing_market.reference_date'));
    }

    /** @return array{usd_try: float, jet_fuel_usd: float, rate_date: string}|null */
    /** @return array{usd_try: float, jet_fuel_usd: float, cpi: float|null, rate_date: string}|null */
    private function toArray(?MarketRate $rate): ?array
    {
        if ($rate === null) {
            return null;
        }

        return [
            'usd_try'      => (float) $rate->usd_try,
            'jet_fuel_usd' => (float) $rate->jet_fuel_usd,
            'cpi'          => $rate->cpi !== null ? (float) $rate->cpi : null,
            'rate_date'    => $rate->rate_date->toDateString(),
        ];
    }
}
