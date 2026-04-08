<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        $clientName = $this->booking->client_name ?? 'Unknown';
        $startsAt   = $this->booking->starts_at
            ? $this->booking->starts_at->format('D d M Y, H:i')
            : 'TBC';

        return new Envelope(
            subject: "New Booking — {$clientName} on {$startsAt}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-booking',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
