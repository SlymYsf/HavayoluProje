<?php

namespace App\Http\Controllers;

use App\Models\Aircraft;
use Illuminate\View\View;

class FleetController extends Controller
{
    /**
     * Filo sayfası: uçak modellerini gruplandırıp sayfaya döner.
     *
     * Aynı model birden çok uçak olarak seed edilmiş (103 uçak, ~10-11
     * farklı model). Sayfada her model için tek kart gösteriyoruz;
     * kaç adet olduğu ve nerede konuşlandığı özet bilgiler.
     */
    public function index(): View
    {
        $models = Aircraft::query()
            ->selectRaw('model, MIN(id) as sample_id, COUNT(*) as unit_count')
            ->groupBy('model')
            ->orderBy('model')
            ->get()
            ->map(function ($row) {
                $sample = Aircraft::find($row->sample_id);

                return [
                    'model'      => $row->model,
                    'unit_count' => (int) $row->unit_count,
                    'body_type'  => $sample->body_type,
                    'range_km'   => $sample->range_km,
                    'capacity'   => $sample->economy_capacity + $sample->business_capacity
                        + ($sample->premium_economy_capacity ?? 0),
                    'slug'       => $this->slugFor($sample->model),
                    'tagline'    => $this->taglineFor($sample->model),
                ];
            });

        return view('static.fleet', ['models' => $models]);
    }

    /**
     * Model adını görsel dosya adına eşleştirir.
     *
     * Dosya adları PROJECT_CONTEXT'te veya seed'de değil, images/fleet
     * klasöründeki gerçek dosya adlarına göre yazıldı; slug fonksiyonuyla
     * türetilemiyor (örneğin B777-300ER → 777-300ER, büyük harf korunur).
     * Yeni bir uçak eklenirse dosya adının buraya işlenmesi gerekir.
     */

    /**
     * Model başına kısa tanıtım metni.
     *
     * Anahtarlar AircraftSeeder'daki model adlarıyla birebir eşleşiyor.
     * Yeni model eklenirse yeni bir satır gerekir; aksi halde varsayılan
     * cümleye düşer.
     */
    private function taglineFor(string $model): string
    {
        return match ($model) {
            'B777-300ER'  => 'Filomuzun bayrak taşıyıcısı: kıtalar arası uzun menzilli seferlerde 349 koltuk kapasitesiyle en geniş uçağımız.',
            'A350-900'    => 'Karbon fiber gövdesi ve sessiz kabini ile çevre dostu, yeni nesil geniş gövde uçağımız.',
            'B787-9'      => 'Düşük kabin basıncı ve büyük pencereleriyle uzun uçuşlarda dinlendirici bir yolculuk deneyimi.',
            'A330-300'    => 'Orta ve uzun menzilli seferlerde geniş kabinin sunduğu ferahlığı yaşatan güvenilir uçağımız.',
            'A321neo'     => 'Yeni nesil motor teknolojisiyle uzayan menzil, düşük yakıt tüketimi ve konforlu kabin sunan dar gövde uçağımız.',
            'A320neo'     => 'Kısa ve orta mesafede verimli, sessiz ve çevreye duyarlı bir yolculuk için tasarlandı.',
            'B737-800'    => 'Kısa ve orta mesafede güvenilir, tanıdık ve verimli iç hat uçağımız.',
            'B737 MAX 8'  => 'Yeni nesil kanat ve motor tasarımıyla daha sessiz, daha az yakıt tüketen modern bir seçenek.',
            default => 'Filomuzun bir parçası olarak sizleri güvenle uçuruyor.',
        };
    }

    private function slugFor(string $model): string
    {
        return match ($model) {
            'B777-300ER'  => '777-300ER',
            'A350-900'    => '350-900',
            'B787-9'      => '787-9',
            'A330-300'    => '330-300',
            'A321neo'     => '321-neo',
            'A320neo'     => '320-neo',
            'B737-800'    => '737-800',
            'B737 MAX 8'  => '737-max-8',
            default       => \Illuminate\Support\Str::slug($model),
        };
    }
}
