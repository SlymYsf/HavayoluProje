<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReservationCancelled extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection $tickets İptal edilmiş biletler (tüm bacaklar)
     */
    public function __construct(
        public string $pnr,
        public Collection $tickets,
        public int $refundTotal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rezervasyonunuz iptal edildi — ' . $this->pnr,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reservation-cancelled');
    }
}
