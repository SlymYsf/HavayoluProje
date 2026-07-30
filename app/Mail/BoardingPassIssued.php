<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BoardingPassIssued extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection $tickets Tek bir uçuş bacağının biletleri.
     *                            İlişkileri yüklenmiş olmalı: passenger,
     *                            flight.route.originAirport/destinationAirport, flight.aircraft
     */
    public function __construct(
        public string $pnr,
        public Collection $tickets,
    ) {}

    public function envelope(): Envelope
    {
        $flight = $this->tickets->first()->flight;

        return new Envelope(
            subject: 'Biniş kartınız hazır — ' . $flight->flight_number . ' · ' . $this->pnr,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.boarding-pass',
        );
    }
}
