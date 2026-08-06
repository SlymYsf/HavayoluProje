<?php

namespace App\Services\Market;

use App\Services\Market\Contracts\ExchangeRateProviderInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TCMB günlük döviz kurları.
 *
 * Anahtar gerektirmiyor. Güncel kur `today.xml`, geçmiş kurlar
 * `kurlar/YYYYMM/DDMMYYYY.xml` adresinden geliyor.
 *
 * TCMB yalnızca iş günleri yayın yapar ve günün kurunu 15:30'dan sonra
 * açıklar. Hafta sonu / resmi tatil istenirse geriye doğru en fazla 7 gün
 * taranır; bu havacılıkta da standart yaklaşımdır (son yayınlanan kur geçerli).
 */
class TcmbExchangeRateProvider implements ExchangeRateProviderInterface
{
    private const BASE = 'https://www.tcmb.gov.tr/kurlar';

    /** Tatil günlerinde geriye doğru kaç gün taranacağı. */
    private const MAX_LOOKBACK_DAYS = 7;

    public function usdTry(?CarbonInterface $date = null): ?float
    {
        $cursor = $date?->copy();

        for ($i = 0; $i <= self::MAX_LOOKBACK_DAYS; $i++) {
            $rate = $this->fetch($cursor);

            if ($rate !== null) {
                return $rate;
            }

            // today.xml denendi ve olmadıysa dünden başlayarak geriye git
            $cursor = $cursor ? $cursor->subDay() : now()->subDay();
        }

        Log::warning('TCMB kuru alınamadı.', ['date' => $date?->toDateString()]);

        return null;
    }

    private function fetch(?CarbonInterface $date): ?float
    {
        $url = $date === null
            ? self::BASE . '/today.xml'
            : self::BASE . '/' . $date->format('Ym') . '/' . $date->format('dmY') . '.xml';

        try {
            $response = Http::timeout(10)->get($url);

            if (! $response->successful()) {
                return null;
            }

            // libxml hatalarını kendi hata yığınımıza al: bozuk XML uyarı
            // basmasın, sessizce null dönsün.
            $previous = libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response->body());
            libxml_use_internal_errors($previous);

            if ($xml === false) {
                return null;
            }

            foreach ($xml->Currency as $currency) {
                if ((string) $currency['CurrencyCode'] !== 'USD') {
                    continue;
                }

                $value = (float) str_replace(',', '.', (string) $currency->ForexSelling);

                return $value > 0 ? $value : null;
            }
        } catch (\Throwable $e) {
            Log::warning('TCMB isteği başarısız.', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return null;
    }
}
