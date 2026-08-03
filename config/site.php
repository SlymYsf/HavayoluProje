<?php

/**
 * Desteklenen dil listesi.
 *
 * Middleware, doğrulama ve arayüz aynı listeyi okur; yeni bir dil eklemek
 * için buraya bir satır eklemek ve lang/<kod>/site.php dosyasını
 * oluşturmak yeterlidir.
 *
 * Ülke/bölge listesi burada tutulmaz: `countries` tablosunda 213 kayıt
 * hâlinde seed edilmiş durumda (bkz. Bölüm 4.13), bayrak görseli de ISO
 * kodundan türetiliyor. Aynı verinin ikinci bir kopyasını tutmamak için
 * bölge seçimi doğrudan veritabanından okunuyor.
 */
return [
    'locales' => [
        'tr' => ['label' => 'Türkçe', 'short' => 'TR'],
        'en' => ['label' => 'English', 'short' => 'EN'],
    ],
];
