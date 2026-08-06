<?php

namespace App\Console\Commands;

use App\Models\MarketRate;
use App\Services\Market\Contracts\ExchangeRateProviderInterface;
use App\Services\Market\Contracts\JetFuelPriceProviderInterface;
use App\Services\Market\Contracts\CpiProviderInterface;
use App\Services\Market\MarketRateService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncMarketRates extends Command
{
    protected $signature = 'market:sync
                            {--date= : Belirli bir günün verisini çeker (Y-m-d)}
                            {--reference : Referans günün verisini çeker}
                            {--cpi-reference : TÜFE referans ayının verisini çeker}';

    protected $description = 'Güncel döviz kuru, jet yakıtı fiyatı ve TÜFE endeksini çekip kaydeder';

    public function handle(
        ExchangeRateProviderInterface $exchange,
        JetFuelPriceProviderInterface $fuel,
        CpiProviderInterface $cpi,
        MarketRateService $service
    ): int {
        $date = $this->resolveDate();

        $this->line($date ? 'Tarih: ' . $date->toDateString() : 'Tarih: güncel');

        $usdTry  = $exchange->usdTry($date);
        $jetFuel = $fuel->pricePerGallon($date);
        $cpiIndex = $cpi->index($date);

        if ($usdTry === null || $jetFuel === null) {
            $this->error(sprintf(
                'Veri eksik — kur: %s, yakıt: %s. Kayıt yapılmadı.',
                $usdTry === null ? 'YOK' : number_format($usdTry, 4),
                $jetFuel === null ? 'YOK' : number_format($jetFuel, 4)
            ));

            return self::FAILURE;
        }

        // TÜFE eksikse kayıt yine de yazılır: kur ve yakıt katmanları çalışır,
        // enflasyon payı yalnızca hareketsiz kalır.
        if ($cpiIndex === null) {
            $this->warn('TÜFE endeksi alınamadı, enflasyon payı hareketsiz sayılacak.');
        }

        $rateDate = $date ?? now();

        $rate = MarketRate::updateOrCreate(
            ['rate_date' => $rateDate->toDateString()],
            ['usd_try' => $usdTry, 'jet_fuel_usd' => $jetFuel, 'cpi' => $cpiIndex]
        );

        $service->forgetCache();

        $this->info(sprintf(
            '%s — USD/TRY: %s · Jet A-1: %s $/galon · TÜFE: %s',
            $rate->rate_date->format('d.m.Y'),
            number_format($usdTry, 4, ',', '.'),
            number_format($jetFuel, 4, ',', '.'),
            $cpiIndex === null ? '—' : number_format($cpiIndex, 2, ',', '.')
        ));

        if ($service->reference()) {
            $this->newLine();
            $this->line('Menzile göre güncel çarpanlar:');

            foreach (array_keys(config('pricing_market.weights')) as $category) {
                $this->line(sprintf('  %-12s %.4f', $category, $service->multiplier($category)));
            }
        } else {
            $this->warn('Referans günün verisi yok. Önce: php artisan market:sync --reference');
        }

        return self::SUCCESS;
    }

    private function resolveDate(): ?Carbon
    {
        if ($this->option('cpi-reference')) {
            return Carbon::parse(config('pricing_market.cpi_reference_month'));
        }

        if ($this->option('reference')) {
            return Carbon::parse(config('pricing_market.reference_date'));
        }

        $date = $this->option('date');

        return $date ? Carbon::parse($date) : null;
    }
}
