{{-- JavaScript tarafına aktarılan metinler.
     __() yalnızca sunucuda çalıştığı için JS'te kullanılan her metin
     buradan geçiyor. Anahtar Türkçe metnin kendisi; karşılığı bulunamazsa
     anahtar basılır, yani site Türkçeyken bu dosya boş dönse bile çalışır.

     Dizi @json() içine doğrudan yazılmıyor: Blade yönlendirme argümanlarını
     düzenli ifadeyle ayrıştırdığı için çok satırlı dizilerde parantez
     dengesini kaybediyor. Önce değişkene alınıyor, sonra basılıyor.

     Yeni bir JS metni eklerken: aşağıya bir satır ekle, JS'te dhT() ile çağır. --}}
@php
    $dhLang = [
        '(Tümü)' => __('(Tümü)'),
        'Tüm havalimanları' => __('Tüm havalimanları'),
        'Tüm uçuş noktalarını gör' => __('Tüm uçuş noktalarını gör'),
        'Havalimanı listesi yüklenemedi.' => __('Havalimanı listesi yüklenemedi.'),
        'Eşleşen uçuş noktası bulunamadı.' => __('Eşleşen uçuş noktası bulunamadı.'),
        'Soldan bir ülke seçin.' => __('Soldan bir ülke seçin.'),
        'Seçtiğiniz kalkış noktasından bu ülkeye uçuş bulunmuyor.' => __('Seçtiğiniz kalkış noktasından bu ülkeye uçuş bulunmuyor.'),
        'Lütfen kalkış noktasını seçin.' => __('Lütfen kalkış noktasını seçin.'),
        'Lütfen varış noktasını seçin.' => __('Lütfen varış noktasını seçin.'),
        'Lütfen gidiş tarihini seçin.' => __('Lütfen gidiş tarihini seçin.'),
        'Gidiş-dönüş araması için dönüş tarihini seçin.' => __('Gidiş-dönüş araması için dönüş tarihini seçin.'),
        'Lütfen en az bir yolcu seçin.' => __('Lütfen en az bir yolcu seçin.'),
        'Yolcu' => __('Yolcu'),
        'Planlandı' => __('Planlandı'),
        'Gecikmeli' => __('Gecikmeli'),
        'İptal' => __('İptal'),
        'Tamamlandı' => __('Tamamlandı'),
        'Uçuş planlandığı saatte gerçekleşecek.' => __('Uçuş planlandığı saatte gerçekleşecek.'),
        'Uçuşta gecikme bildirildi. Güncel saati kontrol edin.' => __('Uçuşta gecikme bildirildi. Güncel saati kontrol edin.'),
        'Bu uçuş iptal edilmiştir. Bilet sahiplerine bilgilendirme yapılır.' => __('Bu uçuş iptal edilmiştir. Bilet sahiplerine bilgilendirme yapılır.'),
        'Uçuş tamamlandı.' => __('Uçuş tamamlandı.'),
        'Arama bilgileri eksik. Lütfen ana sayfadan tekrar deneyin.' => __('Arama bilgileri eksik. Lütfen ana sayfadan tekrar deneyin.'),
        'Uçuş bulunamadı.' => __('Uçuş bulunamadı.'),
        'Uçuş bilgileri alınamadı. Lütfen tekrar deneyin.' => __('Uçuş bilgileri alınamadı. Lütfen tekrar deneyin.'),
        'Arama' => __('Arama'),
        'uçuş' => __('uçuş'),
        'gün' => __('gün'),
        'Uçak' => __('Uçak'),
        'Gecikme sebebi' => __('Gecikme sebebi'),
        'sebebiyle' => __('sebebiyle'),
        'dakika gecikme bildirildi.' => __('dakika gecikme bildirildi.'),
        'Planlanan kalkış:' => __('Planlanan kalkış:'),
        'Yeni sorgulama' => __('Yeni sorgulama'),
        'Check-in\'e git' => __('Check-in\'e git'),
        'Varış havalimanı' => __('Varış havalimanı'),
        'sa' => __('sa'),
        'dk' => __('dk'),
        'Ana sayfaya dön' => __('Ana sayfaya dön'),
        'Uçuş numarası' => __('Uçuş numarası'),
        'Kalkış havalimanı' => __('Kalkış havalimanı'),
        'Güzergâh' => __('Güzergâh'),
        'az önce' => __('az önce'),
        'dakika önce' => __('dakika önce'),
        'saat önce' => __('saat önce'),
    ];
@endphp

<script>
    window.dhLocale = {!! json_encode(app()->getLocale()) !!};
    window.dhLang = {!! json_encode($dhLang, JSON_UNESCAPED_UNICODE) !!};

    window.dhT = function (key) {
        return (window.dhLang && window.dhLang[key]) || key;
    };
</script>
