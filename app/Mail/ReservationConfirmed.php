<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ReservationConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection $tickets İlişkileri yüklenmiş bilet koleksiyonu
     *                            (passenger, flight.route.*, flight.aircraft)
     */
    public function __construct(
        public string $pnr,
        public Collection $tickets,
        public int $total,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rezervasyonunuz tamamlandı — ' . $this->pnr,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation-confirmed',
        );
    }
}
