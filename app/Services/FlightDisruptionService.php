<?php

namespace App\Services;

use App\Mail\FlightDelayed;
use App\Models\Announcement;
use App\Models\Flight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Uçuş aksaklıklarını simüle eder.
 *
 * Gerçek bir havayolunda rötarlar operasyonel sistemlerden gelir; burada
 * demo amacıyla rastgele üretiliyor. Aksaklık üretmek üç iş yapar:
 * uçuşu 'Gecikmeli' işaretler, bir duyuru kaydı açar ve biletli yolcu
 * varsa e-posta gönderir.
 */
class FlightDisruptionService
{
    /**
     * Rötar sebepleri ve süre aralıkları (dakika).
     *
     * Aralıklar bilinçli olarak çakışmıyor: ilk sürümde beş sebebin süreleri
     * iç içeydi ve 75 dakikalık bir rötar dört farklı sebepten gelebiliyordu,
     * bu da sebepleri ayırt edilemez kılıyordu.
     */
    private const REASONS = [
        'Hava trafiği yoğunluğu' => [15, 40],
        'Uçağın geç gelmesi'     => [30, 60],
        'Operasyonel nedenler'   => [45, 80],
        'Teknik bakım'           => [70, 130],
        'Olumsuz hava koşulları' => [90, 200],
    ];

    /**
     * Sebeplerin seçilme ağırlıkları.
     *
     * Eşit olasılık gerçekçi değil: hava trafiği kaynaklı kısa gecikmeler
     * sık görülür, uzun süreli hava koşulu kaynaklı gecikmeler nadirdir.
     */
    private const REASON_WEIGHTS = [
        'Hava trafiği yoğunluğu' => 35,
        'Uçağın geç gelmesi'     => 30,
        'Operasyonel nedenler'   => 20,
        'Teknik bakım'           => 10,
        'Olumsuz hava koşulları' => 5,
    ];

    /** Yalnızca önümüzdeki bu kadar saatteki uçuşlara rötar verilir. */
    private const WINDOW_HOURS = 24;

    /**
     * Rastgele bir uçuşa rötar verir.
     *
     * Zaten gecikmiş ya da iptal edilmiş uçuşlar seçilmez: ikinci bir rötar
     * yolcuya iki ayrı bildirim gönderir ve toplam süre anlamsızlaşır.
     *
     * @return Flight|null Uygun uçuş bulunamazsa null
     */
    public function disruptRandomFlight(): ?Flight
    {
        $flight = Flight::with('route.originAirport', 'route.destinationAirport')
            ->where('status', 'Planlandı')
            ->whereBetween('departure_time', [now(), now()->addHours(self::WINDOW_HOURS)])
            ->inRandomOrder()
            ->first();

        if (! $flight) {
            return null;
        }

        $reason = $this->pickReason();
        [$min, $max] = self::REASONS[$reason];

        // Süre 5'in katına yuvarlanıyor; havayolları rötarı dakika dakika
        // değil yuvarlak aralıklarla duyurur.
        $minutes = (int) (round(random_int($min, $max) / 5) * 5);

        return $this->applyDelay($flight, $minutes, $reason);
    }

    /** Sebebi ağırlıklarına göre seçer. */
    private function pickReason(): string
    {
        $roll = random_int(1, array_sum(self::REASON_WEIGHTS));

        foreach (self::REASON_WEIGHTS as $reason => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $reason;
            }
        }

        return array_key_first(self::REASON_WEIGHTS);
    }

    /**
     * Verilen uçuşa rötar uygular, duyuru açar ve yolculara haber verir.
     *
     * Uçuş güncellemesi ile duyuru kaydı tek işlemde yapılıyor; biri yazılıp
     * diğeri yazılmazsa yolcuya bilgi ulaşmadan uçuş gecikmiş görünürdü.
     * E-posta gönderimi işlem dışında: posta sunucusu hatası veritabanı
     * değişikliğini geri almamalı.
     */
    public function applyDelay(Flight $flight, int $minutes, string $reason): Flight
    {
        DB::transaction(function () use ($flight, $minutes, $reason) {
            $flight->update([
                'status'        => 'Gecikmeli',
                'delay_minutes' => $minutes,
                'delay_reason'  => $reason,
            ]);

            Announcement::create([
                'flight_id'    => $flight->id,
                'type'         => 'delay',
                'title'        => $this->buildTitle($flight, $minutes),
                'body'         => $this->buildBody($flight, $minutes, $reason),
                'published_at' => now(),
                'expires_at'   => $flight->estimatedArrivalTime(),
            ]);
        });

        $this->notifyPassengers($flight);

        return $flight->fresh();
    }

    private function buildTitle(Flight $flight, int $minutes): string
    {
        $route = $flight->route;

        return sprintf(
            '%s %s-%s seferi %d dakika gecikmeli',
            $flight->flight_number,
            $route->originAirport->iata_code,
            $route->destinationAirport->iata_code,
            $minutes
        );
    }

    private function buildBody(Flight $flight, int $minutes, string $reason): string
    {
        $route = $flight->route;

        return sprintf(
            '%s sebebiyle %s tarihli %s (%s → %s) seferimiz %d dakika gecikmeli kalkacaktır. '
            . 'Planlanan kalkış: %s. Tahmini kalkış: %s. Anlayışınız için teşekkür ederiz.',
            $reason,
            $flight->departure_time->format('d.m.Y'),
            $flight->flight_number,
            $route->originAirport->city,
            $route->destinationAirport->city,
            $minutes,
            $flight->departure_time->format('H:i'),
            $flight->estimatedDepartureTime()->format('H:i')
        );
    }

    /**
     * Uçuşta bileti olan yolculara e-posta gönderir.
     *
     * İptal edilmiş biletler dışarıda bırakılıyor. Aynı PNR'da birden çok
     * bilet olabilir; e-posta yolcu bazında değil, iletişim adresi bazında
     * tekilleştiriliyor ki aile bileti alan kişiye aynı mail üç kez gitmesin.
     */
    private function notifyPassengers(Flight $flight): void
    {
        $tickets = $flight->tickets()
            ->with('passenger')
            ->where('status', '!=', 'cancelled')
            ->get();

        $tickets->groupBy(fn ($ticket) => $ticket->passenger->email)
            ->each(function ($group, $email) use ($flight) {
                if (! $email) {
                    return;
                }

                Mail::to($email)->send(new FlightDelayed($flight, $group));
            });
    }
}
