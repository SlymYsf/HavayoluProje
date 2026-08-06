<?php

/*
|--------------------------------------------------------------------------
| Piyasa Bazlı Fiyatlandırma
|--------------------------------------------------------------------------
|
| `routes.base_price` değerleri referans tarihindeki piyasa koşullarına göre
| tanımlandı. Kur ve yakıt fiyatı o günden bu yana ne kadar değiştiyse bilet
| fiyatı da o oranda hareket eder.
|
| Referans değerleri buraya elle YAZILMAZ; `market:sync --reference` komutu
| TCMB ve EIA'dan çekip veritabanına yazar. Böylece sayı uydurma riski yok.
|
*/

return [

    'reference_date' => env('PRICING_REFERENCE_DATE', '2026-07-01'),

    /*
    | TÜFE referansı ayrı tutuluyor çünkü endeks AYLIKTIR ve bir ayın verisi
    | takip eden ayın ~3'ünde yayınlanır. `base_price` 1 Temmuz'da yazılırken
    | elde bilinen son endeks Haziran'ınkiydi; referans bu yüzden Haziran.
    | Referans ayı Temmuz yapsaydık, Ağustos endeksi yayınlanana kadar oran
    | 1,000'de sabit kalır ve katman hiç çalışmazdı.
    */
    'cpi_reference_month' => env('PRICING_CPI_REFERENCE_MONTH', '2026-06-01'),

    /*
    | Maliyet payları. Üçü toplamda 1,00 eder:
    |   fuel : jet yakıtı (dolar bazlı, kurla birlikte TL'ye çevriliyor)
    |   fx   : dövize bağlı diğer giderler (uçak kirası, bakım, sigorta)
    |   cpi  : TL cinsinden giderler (personel, yer hizmetleri, havalimanı ücreti)
    |
    | Yakıtın payı mesafeyle artar, TL giderlerin payı azalır: kısa uçuşta
    | yer hizmetleri ve personel baskın, uzun uçuşta yakıt baskındır.
    */
    'weights' => [
        'short'      => ['fuel' => 0.20, 'fx' => 0.25, 'cpi' => 0.55],  // iç hat
        'medium'     => ['fuel' => 0.25, 'fx' => 0.30, 'cpi' => 0.45],
        'long'       => ['fuel' => 0.32, 'fx' => 0.30, 'cpi' => 0.38],
        'ultra_long' => ['fuel' => 0.38, 'fx' => 0.30, 'cpi' => 0.32],
    ],

    /*
    | Güvenlik sınırı. Kaynak bozuk veri dönerse (0, null, hatalı ondalık)
    | fiyat ne sıfırlanır ne de uçar.
    */
    'bounds' => [
        'min' => 0.75,
        'max' => 2.50,
    ],

    'cache_ttl_minutes' => 720,
];
