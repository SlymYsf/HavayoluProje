<?php

namespace App\Services\Market;

use App\Services\Market\Contracts\CpiProviderInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * TCMB EVDS — Tüketici Fiyat Endeksi (TP.FE.OKTG01).
 *
 * Endeks aylıktır ve bir ayın verisi takip eden ayın ~3'ünde yayınlanır.
 * Bu yüzden tek ay değil, geriye doğru bir aralık sorgulanıp yayınlanmış
 * son değer alınır; aksi halde ayın ilk günlerinde veri hep boş dönerdi.
 *
 * EVDS'nin iki özelliği var, ikisi de standart dışı:
 *
 *  1. Parametreler sorgu dizesi olarak DEĞİL, doğrudan yol üzerinde bekleniyor:
 *     `service/evds/series=...&startDate=...` — başında `?` yok. Laravel'in
 *     query dizisi `service/evds/?series=...` üretiyor ve EVDS bunu tanımıyor.
 *
 *  2. API anahtarı 5 Nisan 2024'ten beri URL'de değil, HTTP BAŞLIĞINDA
 *     gönderiliyor. URL'ye konursa istek portala yönleniyor ve JSON yerine
 *     HTML dönüyor. Bu yüzden servis tarayıcıdan elle test edilemiyor.
 */
class EvdsCpiProvider implements CpiProviderInterface
{

    private const SERIES = 'TP.FE.OKTG01';

    /** Yayın gecikmesini karşılamak için geriye doğru taranan ay sayısı. */
    private const LOOKBACK_MONTHS = 6;

    public function index(?CarbonInterface $date = null): ?float
    {
        $apiKey = config('services.evds.key');

        if (blank($apiKey)) {
            Log::warning('EVDS anahtarı tanımlı değil, TÜFE çekilemiyor.');

            return null;
        }

        $end   = ($date ?? now())->copy();
        $start = $end->copy()->subMonths(self::LOOKBACK_MONTHS);

        $base = rtrim(config('services.evds.base_url'), '/') . '/';

        $url = $base . implode('&', [
                'series=' . self::SERIES,
                'startDate=' . $start->format('d-m-Y'),
                'endDate=' . $end->format('d-m-Y'),
                'type=json',
            ]);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['key' => $apiKey])
                ->get($url);

            if (! $response->successful()) {
                Log::warning('EVDS isteği başarısız.', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 300),
                ]);

                return null;
            }

            // EVDS alan adında noktaları alt çizgiye çeviriyor: TP.FE.OKTG01 -> TP_FE_OKTG01
            $field = str_replace('.', '_', self::SERIES);
            $items = $response->json('items', []);

            if (empty($items)) {
                Log::warning('EVDS yanıtı boş.', [
                    'url'  => $url,
                    'body' => mb_substr($response->body(), 0, 300),
                ]);

                return null;
            }

            // Yayınlanmış son değer: sondan başa doğru ilk dolu kayıt
            foreach (array_reverse($items) as $item) {
                $value = $item[$field] ?? null;

                if (is_numeric($value) && $value > 0) {
                    return (float) $value;
                }
            }

            Log::warning('EVDS yanıtında kullanılabilir TÜFE değeri yok.', [
                'expected_field' => $field,
                'first_item'     => $items[0] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('EVDS isteği hata verdi.', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
