<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CabinClassController extends Controller
{
    /**
     * Kabin sınıfı tanıtım sayfası.
     *
     * İki sekme (Business / Economy) tek sayfada; hangi sınıfın önce açılacağı
     * URL parametresinden geliyor. İçerik burada dizi olarak tutuluyor;
     * sabit tanıtım metinleri veritabanına gerek duymuyor, ama filoyla ilgili
     * kısımlar (kaç uçakta hangi kabin var) canlı verinin sonucudur.
     */
    public function show(string $class = 'business'): View
    {
        $class = in_array($class, ['business', 'economy'], true) ? $class : 'business';

        return view('static.cabin', [
            'active'  => $class,
            'classes' => $this->classes(),
        ]);
    }

    private function classes(): array
    {
        return [
            'business' => [
                'title'    => 'Business Class',
                'tagline'  => 'Ödüllü ikramlar, uzayabilen koltuklar ve gökyüzünde kesintisiz iş akışı.',
                'hero'     => 'business.jpg',
                'intro'    => 'Business Class ile yolculuğunuz uçağa binmeden başlar. Ayrıcalıklı check-in kuyruğu, geniş bagaj hakkı ve havalimanı loungeları ile seyahatiniz baştan sona konforlu bir deneyime dönüşür. Uçakta ise şef mönüsü, tam yatak konumuna gelen koltuklar ve kişisel eğlence sistemi sizi bekliyor.',
                'features' => [
                    ['icon' => 'ti-armchair',      'title' => 'Tam yatak koltuklar', 'body' => 'Uzun uçuşlarda 180 dereceye kadar uzayan, gerçek yatak konumuna gelen koltuklarla dinlendirici bir yolculuk.'],
                    ['icon' => 'ti-chef-hat',      'title' => 'Şef mönüsü',           'body' => 'Uçak içi ikramda özel şef seçkileri, mevsimlik mönü ve zenginleştirilmiş içecek seçenekleri.'],
                    ['icon' => 'ti-briefcase',     'title' => 'Ayrıcalıklı check-in', 'body' => 'Ayrı check-in bankosu ve öncelikli güvenlik kontrolü ile havalimanında zaman kazanın.'],
                    ['icon' => 'ti-luggage',       'title' => 'Ekstra bagaj hakkı',   'body' => 'Kabin bagajının yanında 40 kg\'a kadar kayıtlı bagaj taşıma hakkı.'],
                    ['icon' => 'ti-building',      'title' => 'Lounge erişimi',       'body' => 'İstanbul Havalimanı ve seçili duraklardaki iş yolcusu loungelarına ücretsiz giriş.'],
                    ['icon' => 'ti-headphones',    'title' => 'Kişisel eğlence',       'body' => 'Büyük ekranlar, yüksek ses yalıtımlı kulaklıklar ve geniş film-müzik arşivi.'],
                ],
            ],
            'economy' => [
                'title'    => 'Economy Class',
                'tagline'  => 'Uygun ücretlerle güvenli ve konforlu bir seyahat deneyimi.',
                'hero'     => 'economy.jpg',
                'intro'    => 'Economy Class ile bütçenize uygun fiyatlarla dünyanın dört bir yanına ulaşırsınız. Modern kabin tasarımı, taze hazırlanan ikramlar ve uçak içi eğlence sistemiyle uçuşunuz keyifli geçer. Yeni nesil koltuklar ergonomik bir oturuşla uzun uçuşlarda bile rahat bir yolculuk sunar.',
                'features' => [
                    ['icon' => 'ti-armchair',      'title' => 'Ergonomik koltuklar',   'body' => 'Yeni nesil ince tasarım koltuklar, korunan diz mesafesi ve ayarlanabilir baş desteği.'],
                    ['icon' => 'ti-tools-kitchen','title' => 'Sıcak ikram',           'body' => 'Uçuş süresine göre değişen taze hazırlanmış yemek ve içecek servisi.'],
                    ['icon' => 'ti-device-tv',     'title' => 'Uçak içi eğlence',      'body' => 'Kişisel ekranlarda film, müzik, oyun ve çocuk kanalı seçenekleri.'],
                    ['icon' => 'ti-luggage',       'title' => 'Kabin bagajı',          'body' => 'Kabinde bir el bagajı ve tanımlı kayıtlı bagaj hakkıyla rahat seyahat.'],
                    ['icon' => 'ti-mood-kid',      'title' => 'Aileye uygun',          'body' => 'Bebek ve çocuk yolcular için özel ikramlar ve eğlence içeriği.'],
                    ['icon' => 'ti-wifi',          'title' => 'Bağlantıda kalın',      'body' => 'Seçili uzun menzilli uçuşlarda uçak içi Wi-Fi ile bağlantıda kalın.'],
                ],
            ],
        ];
    }
}
