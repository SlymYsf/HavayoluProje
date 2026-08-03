<?php

namespace App\Mail;

use App\Models\Flight;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;

class FlightDelayed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Flight $flight,
        public Collection $tickets,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '%s seferiniz %d dakika gecikmeli',
                $this->flight->flight_number,
                $this->flight->delay_minutes
            ),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.flight-delayed');
    }
}
