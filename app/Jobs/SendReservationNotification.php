<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Notifications\NotificationType;
use App\Services\Notifications\ReservationNotifier;
use App\Services\Sms\SmsGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\TicketReminder;

/**
 * Bir rezervasyon bacağı için e-posta ve SMS bildirimi gönderir.
 *
 * Üç bildirim türünün tamamı bu iş üzerinden geçer: rezervasyon onayı,
 * biniş kartı ve check-in hatırlatması. Gönderim istek içinde değil işçi
 * süreçte yapıldığı için kullanıcı beklemiyor ve başarısız gönderimler
 * yeniden deneniyor.
 */
class SendReservationNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** SMTP ya da SMS sağlayıcısı geçici hata verirse üç kez denenir. */
    public int $tries = 3;

    /** Yeniden denemeler arası artan bekleme (saniye). */
    public array $backoff = [60, 300];

    public int $timeout = 60;

    /**
     * Model yerine kimlik listesi taşınıyor: kuyrukta bekleyen iş eski
     * veriyle çalışmasın, işlenirken güncel kaydı okusun.
     *
     * @param int[] $ticketIds
     */
    public function __construct(
        public NotificationType $type,
        public string $pnr,
        public array $ticketIds,
        public ?int $reminderId = null,
    ) {}

    public function handle(ReservationNotifier $notifier, SmsGatewayInterface $sms): void
    {
        $tickets = Ticket::with([
            'passenger',
            'flight.route.originAirport',
            'flight.route.destinationAirport',
            'flight.aircraft',
        ])
            ->whereIn('id', $this->ticketIds)
            ->orderBy('id')
            ->get();

        if ($tickets->isEmpty()) {
            $this->closeReminder('cancelled');
            return;
        }

        if (! $this->stillRelevant($tickets)) {
            $this->closeReminder('cancelled');
            return;
        }

        $passenger = $tickets->first()->passenger;

        if ($passenger->email) {
            $mailable = $notifier->mailable($this->type, $this->pnr, $tickets);

            if ($mailable) {
                Mail::to($passenger->email)->send($mailable);
            }
        }

        if ($passenger->phone) {
            $result = $sms->send($passenger->phone, $notifier->smsText($this->type, $this->pnr, $tickets));

            if (! $result->success) {
                // İstisna fırlatıyoruz ki iş yeniden denensin
                throw new \RuntimeException('SMS gönderilemedi: ' . $result->message);
            }
        }

        $this->closeReminder('sent');
    }

    /** Zamanlanmış bir hatırlatmadan geldiyse kaydı sonuçlandırır. */
    private function closeReminder(string $status): void
    {
        if (! $this->reminderId) {
            return;
        }

        TicketReminder::where('id', $this->reminderId)->update([
            'status'  => $status,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    /**
     * Bildirim hâlâ anlamlı mı?
     *
     * Kuyrukta beklerken durum değişmiş olabilir: bilet iptal edilmiş ya da
     * hatırlatma beklerken kullanıcı kendisi check-in yapmış olabilir.
     */
    private function stillRelevant($tickets): bool
    {
        // İptal bildirimi zaten iptal edilmiş biletler için gönderilir —
        // aşağıdaki kontrolden muaf tutulmalı.
        if ($this->type === NotificationType::ReservationCancelled) {
            return true;
        }

        if ($tickets->every(fn (Ticket $t) => $t->status === 'cancelled')) {
            return false;
        }

        if ($this->type === NotificationType::CheckInReminder
            && $tickets->every(fn (Ticket $t) => $t->checked_in_at !== null)) {
            return false;
        }

        return true;
    }

    /** Üç deneme de başarısız olduğunda çağrılır. */
    public function failed(\Throwable $e): void
    {
        Log::critical('Rezervasyon bildirimi gönderilemedi.', [
            'type'       => $this->type->value,
            'pnr'        => $this->pnr,
            'ticket_ids' => $this->ticketIds,
            'error'      => $e->getMessage(),
        ]);

        // Kayıt 'queued' durumunda kalmasın; bir sonraki taramada tekrar
        // denenebilmesi için 'pending'e döndürüyoruz.
        if ($this->reminderId) {
            TicketReminder::where('id', $this->reminderId)->update([
                'status'     => 'pending',
                'last_error' => \Illuminate\Support\Str::limit($e->getMessage(), 500),
            ]);
        }
    }


}
