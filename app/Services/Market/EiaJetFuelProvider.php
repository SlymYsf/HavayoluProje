<?php

namespace App\Services\Market;

use App\Services\Market\Contracts\JetFuelPriceProviderInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EIA (ABD Enerji Enformasyon İdaresi) jet yakıtı spot fiyatı.
 *
 * Seri: EER_EPJK_PF4_RGC_DPG — ABD Körfez Kıyısı kerosen tipi jet yakıtı,
 * FOB, dolar/galon. Havacılık sektörünün standart referans fiyatı; yakıt
 * havayolu işletme maliyetinin %20-40'ını oluşturur.
 *
 * Borsa kapalıyken (hafta sonu, tatil) veri yayınlanmaz. Bu yüzden tek gün
 * yerine bir aralık sorgulanıp en yeni kayıt alınır.
 */
class EiaJetFuelProvider implements JetFuelPriceProviderInterface
{
    private const ENDPOINT = 'https://api.eia.gov/v2/petroleum/pri/spt/data/';
    private const SERIES   = 'EER_EPJK_PF4_RGC_DPG';

    /** Veri bulunana kadar geriye doğru taranacak gün sayısı. */
    private const LOOKBACK_DAYS = 14;

    public function pricePerGallon(?CarbonInterface $date = null): ?float
    {
        $apiKey = config('services.eia.key');

        if (blank($apiKey)) {
            Log::warning('EIA anahtarı tanımlı değil, yakıt fiyatı çekilemiyor.');

            return null;
        }

        $end   = ($date ?? now())->copy();
        $start = $end->copy()->subDays(self::LOOKBACK_DAYS);

        try {
            $response = Http::timeout(15)->get(self::ENDPOINT, [
                'api_key'             => $apiKey,
                'frequency'           => 'daily',
                'data[0]'             => 'value',
                'facets[series][]'    => self::SERIES,
                'start'               => $start->toDateString(),
                'end'                 => $end->toDateString(),
                'sort[0][column]'     => 'period',
                'sort[0][direction]'  => 'desc',
                'length'              => 1,
            ]);

            if (! $response->successful()) {
                Log::warning('EIA isteği başarısız.', ['status' => $response->status()]);

                return null;
            }

            $value = $response->json('response.data.0.value');

            return is_numeric($value) && $value > 0 ? (float) $value : null;
        } catch (\Throwable $e) {
            Log::warning('EIA isteği hata verdi.', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
