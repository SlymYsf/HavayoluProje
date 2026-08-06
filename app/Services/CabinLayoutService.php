<?php

namespace App\Services;

use App\Models\Aircraft;

/**
 * Uçak modeline göre kabin planı (seat map) üretir.
 *
 * Bu servis koltuk düzeninin TEK doğru kaynağıdır. Hem manuel koltuk seçimi
 * ekranı hem de TicketService'in otomatik koltuk ataması buradan beslenir —
 * ikisinin ayrışması yapısal olarak imkânsız hale getirildi.
 *
 * Sıra sayısı config'de tutulmaz, `aircrafts` tablosundaki koltuk sayısından
 * hesaplanır. Koltuk sayısı sıra genişliğine tam bölünmezse son sıra eksik
 * kalır: koltuklar gövde ekseninden dışarı doğru dolar, pencere kenarları
 * boşluk olarak işaretlenir — gerçek kabinlerde de arka uç daralır.
 * Bu sayede üretilen koltuk sayısı `total_capacity` ile birebir tutar.
 *
 * Premium Economy 6 Ağustos 2026'da kaldırıldı; koltukları Economy'ye
 * devredildi. Kabin sırası artık yalnızca business ve economy.
 */
class CabinLayoutService
{
    /** Koltuk tipleri. */
    public const TYPE_STANDARD      = 'standard';
    public const TYPE_FRONT_ROW     = 'front_row';
    public const TYPE_EXTRA_LEGROOM = 'extra_legroom';
    public const TYPE_EXIT          = 'exit';
    public const TYPE_BASSINET      = 'bassinet';

    /** Kabinlerin uçağın önünden arkasına sırası. */
    public const CABIN_ORDER = ['business', 'economy'];

    /** Acil çıkış sırasına oturamayacak yolcu tipleri (tahliye görevi üstlenemezler). */
    public const EXIT_ROW_FORBIDDEN_TYPES = ['child', 'infant'];

    /** Bebek pusetli koltuk yalnızca bebekli rezervasyonlara açıktır. */
    public const BASSINET_REQUIRES_INFANT = true;

    /**
     * Uçağın tüm kabinlerini içeren tam plan.
     *
     * @return array{
     *     aircraft: array{model: string, body_type: string, total_capacity: int},
     *     cabins: array<string, array>
     * }
     */
    public function buildMap(Aircraft $aircraft): array
    {
        $cabins   = [];
        $rowStart = 1;

        foreach (self::CABIN_ORDER as $cabinClass) {
            $seatCount = $this->cabinSeatCount($aircraft, $cabinClass);

            if ($seatCount <= 0) {
                continue; // koltuğu olmayan kabin çizilmez
            }

            $cabin = $this->buildCabin($aircraft, $cabinClass, $seatCount, $rowStart);

            $cabins[$cabinClass] = $cabin;
            $rowStart = $cabin['row_end'] + 1;
        }

        return [
            'aircraft' => [
                'model'          => $aircraft->model,
                'body_type'      => $aircraft->body_type,
                'total_capacity' => (int) $aircraft->total_capacity,
            ],
            'cabins' => $cabins,
        ];
    }

    /**
     * Tek bir kabinin planı.
     *
     * @return array{
     *     cabin_class: string, pattern: int[], letters: string[],
     *     seats_per_row: int, seat_count: int, row_start: int, row_end: int,
     *     rows: array<int, array{number: int, type: string, blocks: array}>
     * }
     */
    public function buildCabin(Aircraft $aircraft, string $cabinClass, int $seatCount, int $rowStart): array
    {
        $layout  = $this->layoutFor($aircraft, $cabinClass);
        $pattern = $layout['pattern'];
        $letters = $this->lettersFor($pattern, $layout);
        $perRow  = count($letters);

        $rowSizes = $this->rowSizes($seatCount, $perRow, $layout);

        $rowCount = count($rowSizes);

        $exitOffsets = array_values(array_filter(
            $layout['exit_offsets'] ?? [],
            fn ($offset) => $offset >= 0 && $offset < $rowCount
        ));

        $frontRows       = (int) ($layout['front_rows'] ?? 0);
        $bassinetLetters = $layout['bassinet_letters'] ?? [];

        $rows = [];

        foreach ($rowSizes as $offset => $seatsInRow) {
            $rowNumber = $rowStart + $offset;
            $rowType   = $this->rowType($offset, $exitOffsets, $frontRows);

            $rows[] = [
                'number' => $rowNumber,
                'type'   => $rowType,
                'blocks' => $this->buildBlocks(
                    $rowNumber,
                    $pattern,
                    $letters,
                    $seatsInRow,
                    $rowType,
                    $offset === 0 ? $bassinetLetters : []
                ),
            ];
        }

        return [
            'cabin_class'   => $cabinClass,
            'pattern'       => $pattern,
            'letters'       => $letters,
            'seats_per_row' => $perRow,
            'seat_count'    => $seatCount,
            'row_start'     => $rowStart,
            'row_end'       => $rowStart + $rowCount - 1,
            'rows'          => $rows,
        ];
    }

    /**
     * Kabindeki sıraların koltuk sayıları, önden arkaya.
     *
     * `tail_width` verilmişse gövde kuyrukta daralır: son sıralar yalnızca orta
     * bloktan oluşur. Daralmada kaybedilen koltuklar öndeki tam sıralara
     * dağıtıldığı için toplam DEĞİŞMEZ.
     *
     * `tail_width` yoksa tek bir eksik sıra oluşur; `partial_at` bunun nereye
     * konacağını söyler. Business kabini gövdenin ortasında bittiği için eksik
     * sırası öne (burna) alınır — arkaya konsa gövde ortasında daralma varmış
     * gibi görünüyordu.
     *
     * @return int[] sıra başına koltuk sayısı
     */
    private function rowSizes(int $seatCount, int $perRow, array $layout): array
    {
        $tailWidth   = $layout['tail_width'] ?? null;
        $tailRowsMin = (int) ($layout['tail_rows_min'] ?? 1);

        if ($tailWidth !== null && $tailWidth > 0 && $tailWidth < $perRow) {
            for ($tailCount = max(1, $tailRowsMin); $tailCount < $tailRowsMin + 4; $tailCount++) {
                $remaining = $seatCount - ($tailCount * $tailWidth);

                if ($remaining < $perRow) {
                    break;
                }

                $fullRows   = intdiv($remaining, $perRow);
                $transition = $remaining % $perRow;

                if ($transition !== 0 && $transition <= $tailWidth) {
                    continue;
                }

                $sizes = array_fill(0, $fullRows, $perRow);

                if ($transition > 0) {
                    $sizes[] = $transition;
                }

                return array_merge($sizes, array_fill(0, $tailCount, $tailWidth));
            }
        }

        $rowCount  = (int) ceil($seatCount / $perRow);
        $remainder = $seatCount % $perRow;
        $sizes     = array_fill(0, $rowCount, $perRow);

        if ($remainder > 0) {
            $index = ($layout['partial_at'] ?? 'back') === 'front' ? 0 : $rowCount - 1;
            $sizes[$index] = $remainder;
        }

        return $sizes;
    }

    private function rowType(int $offset, array $exitOffsets, int $frontRows): string
    {
        if (in_array($offset, $exitOffsets, true)) {
            return self::TYPE_EXIT;
        }

        if ($offset === 0 || in_array($offset - 1, $exitOffsets, true)) {
            return self::TYPE_EXTRA_LEGROOM;
        }

        if ($offset < $frontRows) {
            return self::TYPE_FRONT_ROW;
        }

        return self::TYPE_STANDARD;
    }

    /**
     * Bir sıranın bloklarını (koridorla ayrılmış koltuk grupları) üretir.
     *
     * Eksik koltuklar `seat => null` olarak döner; arayüz bunları boşluk çizer.
     */
    private function buildBlocks(
        int $rowNumber,
        array $pattern,
        array $letters,
        int $seatsInRow,
        string $rowType,
        array $bassinetLetters
    ): array {
        $present     = $this->presentPositions(count($letters), $seatsInRow);
        $blocks      = [];
        $letterIndex = 0;

        foreach ($pattern as $blockSize) {
            $block = [];

            for ($i = 0; $i < $blockSize; $i++) {
                $letter = $letters[$letterIndex] ?? null;
                $exists = $letter !== null && in_array($letterIndex, $present, true);

                $block[] = [
                    'seat'   => $exists ? $rowNumber . $letter : null,
                    'letter' => $letter,
                    'type'   => $exists
                        ? $this->seatTypeIn($rowType, $letter, $bassinetLetters)
                        : null,
                ];

                $letterIndex++;
            }

            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * Eksik sırada hangi koltuk pozisyonlarının bulunduğu.
     *
     * Koltuklar gövde ekseninden dışarı doğru dolar: gerçek uçaklarda kabinin
     * arka ucu daraldığı için önce pencere koltukları düşer, orta blok en sona
     * kadar kalır. Soldan sağa doldurmak, gövdenin sağ tarafı kesilmiş gibi
     * görünmesine sebep oluyordu.
     *
     * @return int[] 0 tabanlı harf indeksleri, artan sırada
     */
    private function presentPositions(int $perRow, int $seatsInRow): array
    {
        if ($seatsInRow >= $perRow) {
            return range(0, $perRow - 1);
        }

        $center    = ($perRow - 1) / 2;
        $positions = range(0, $perRow - 1);

        // Eksene yakınlığa göre sırala; eşit uzaklıkta soldaki önce gelsin
        usort($positions, function ($a, $b) use ($center) {
            return [abs($a - $center), $a] <=> [abs($b - $center), $b];
        });

        $present = array_slice($positions, 0, $seatsInRow);
        sort($present);

        return $present;
    }

    /**
     * Koltuğun tipi. Puset, sıra tipinin üzerine biner (acil çıkış hariç):
     * bölme duvarına monte edildiği için kabinin ilk sırasındadır.
     */
    private function seatTypeIn(string $rowType, string $letter, array $bassinetLetters): string
    {
        if ($rowType === self::TYPE_EXIT) {
            return self::TYPE_EXIT;
        }

        if (in_array($letter, $bassinetLetters, true)) {
            return self::TYPE_BASSINET;
        }

        return $rowType;
    }

    /**
     * Kabindeki tüm koltuk numaraları, önden arkaya sıralı.
     * TicketService'in otomatik ataması bunu kullanır.
     *
     * @return string[]
     */
    public function seatNumbers(Aircraft $aircraft, string $cabinClass): array
    {
        $map = $this->buildMap($aircraft);

        if (! isset($map['cabins'][$cabinClass])) {
            throw new \InvalidArgumentException(
                "'{$cabinClass}' kabini bu uçakta bulunmuyor: {$aircraft->model}"
            );
        }

        $seats = [];

        foreach ($map['cabins'][$cabinClass]['rows'] as $row) {
            foreach ($row['blocks'] as $block) {
                foreach ($block as $seat) {
                    if ($seat['seat'] !== null) {
                        $seats[] = $seat['seat'];
                    }
                }
            }
        }

        return $seats;
    }

    /**
     * Koltuk numarası => ['cabin_class' => ..., 'type' => ...] eşlemesi.
     * Tek koltuk sorgusu için buildMap'i her seferinde dolaşmaya gerek kalmaz.
     *
     * @return array<string, array{cabin_class: string, type: string}>
     */
    public function seatIndex(Aircraft $aircraft): array
    {
        $index = [];

        foreach ($this->buildMap($aircraft)['cabins'] as $cabinClass => $cabin) {
            foreach ($cabin['rows'] as $row) {
                foreach ($row['blocks'] as $block) {
                    foreach ($block as $seat) {
                        if ($seat['seat'] !== null) {
                            $index[$seat['seat']] = [
                                'cabin_class' => $cabinClass,
                                'type'        => $seat['type'],
                            ];
                        }
                    }
                }
            }
        }

        return $index;
    }

    /** Tek bir koltuğun tipi; koltuk uçakta yoksa null. */
    public function seatType(Aircraft $aircraft, string $seatNumber): ?string
    {
        return $this->seatIndex($aircraft)[$seatNumber]['type'] ?? null;
    }

    /**
     * Koltuk ücreti.
     *
     * Ücret yalnızca yapılandırmada "ücretli" işaretli kabinlerde (Economy)
     * alınır; tarifede yer almayan koltuk tipleri (standart, bebek pusetli)
     * ücretsizdir.
     *
     * @param string $rangeCategory Route::getRangeCategory() çıktısı
     */
    public function fee(string $cabinClass, ?string $seatType, string $rangeCategory): float
    {
        if ($seatType === null) {
            return 0.0;
        }

        $paidCabins = config('cabin_layouts.fees.paid_cabins', []);

        if (! in_array($cabinClass, $paidCabins, true)) {
            return 0.0;
        }

        $tariff = config("cabin_layouts.fees.tariff.{$seatType}");

        if (! is_array($tariff)) {
            return 0.0;
        }

        return (float) ($tariff[$rangeCategory] ?? 0.0);
    }

    /**
     * Belirli bir kabin ve menzil için tüm koltuk tiplerinin ücret listesi.
     * Arayüzün lejantında göstermesi için.
     *
     * @return array<string, float>
     */
    public function feeTable(string $cabinClass, string $rangeCategory): array
    {
        $types = [
            self::TYPE_STANDARD,
            self::TYPE_FRONT_ROW,
            self::TYPE_EXTRA_LEGROOM,
            self::TYPE_EXIT,
            self::TYPE_BASSINET,
        ];

        $table = [];

        foreach ($types as $type) {
            $table[$type] = $this->fee($cabinClass, $type, $rangeCategory);
        }

        return $table;
    }

    /**
     * Bu yolcu tipi bu koltuğa oturabilir mi?
     *
     * @param bool $reservationHasInfant Rezervasyonda bebek var mı (puset kuralı)
     */
    public function canOccupy(?string $seatType, string $passengerType, bool $reservationHasInfant = false): bool
    {
        if ($seatType === self::TYPE_EXIT) {
            return ! in_array($passengerType, self::EXIT_ROW_FORBIDDEN_TYPES, true);
        }

        if ($seatType === self::TYPE_BASSINET && self::BASSINET_REQUIRES_INFANT) {
            return $reservationHasInfant;
        }

        return true;
    }

    /** Yolcu tipi bu koltuğa oturamıyorsa gösterilecek sebep. */
    public function occupancyError(string $seatNumber, ?string $seatType): string
    {
        return match ($seatType) {
            self::TYPE_EXIT     => "{$seatNumber} acil çıkış koltuğudur; çocuk ve bebek yolcular bu koltuğa oturamaz.",
            self::TYPE_BASSINET => "{$seatNumber} bebek pusetli koltuktur; yalnızca bebekli rezervasyonlara açıktır.",
            default             => "{$seatNumber} koltuğu bu yolcu için seçilemez.",
        };
    }

    /**
     * Kabinin `aircrafts` tablosundaki koltuk sayısı.
     *
     * `premium_economy` bilinçli olarak tanınmıyor: kabin satıştan kalktı ve
     * bir yerden hâlâ o değer geçiyorsa sessizce boş plan üretmek yerine
     * yüksek sesle hata vermesi doğru.
     */
    public function cabinSeatCount(Aircraft $aircraft, string $cabinClass): int
    {
        return (int) match ($cabinClass) {
            'business' => $aircraft->business_seats,
            'economy'  => $aircraft->economy_seats,
            default    => throw new \InvalidArgumentException("Bilinmeyen kabin sınıfı: {$cabinClass}"),
        };
    }

    /** Model için yapılandırma; model tanımsızsa gövde tipine göre yedek düzen. */
    private function layoutFor(Aircraft $aircraft, string $cabinClass): array
    {
        $layout = config("cabin_layouts.models.{$aircraft->model}.{$cabinClass}")
            ?? config("cabin_layouts.fallback.{$aircraft->body_type}.{$cabinClass}");

        if (! is_array($layout)) {
            throw new \RuntimeException(
                "Kabin düzeni tanımlı değil: {$aircraft->model} / {$cabinClass}"
            );
        }

        return $layout;
    }

    /** Dizilime göre harf seti; yapılandırmada `letters` varsa o kullanılır. */
    private function lettersFor(array $pattern, array $layout): array
    {
        if (! empty($layout['letters'])) {
            return $layout['letters'];
        }

        $width   = array_sum($pattern);
        $letters = config("cabin_layouts.letter_sets.{$width}");

        if (! is_array($letters)) {
            throw new \RuntimeException("{$width} koltukluk sıra için harf seti tanımlı değil.");
        }

        return $letters;
    }
}
