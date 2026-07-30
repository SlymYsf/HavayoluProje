<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CheckInReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection $tickets Tek bir uçuş bacağının biletleri
     */
    public function __construct(
        public string $pnr,
        public Collection $tickets,
        public string $checkInUrl,
    ) {}

    public function envelope(): Envelope
    {
        $flight = $this->tickets->first()->flight;

        return new Envelope(
            subject: 'Check-in açıldı — ' . $flight->flight_number . ' · ' . $this->pnr,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.check-in-reminder');
    }
}
