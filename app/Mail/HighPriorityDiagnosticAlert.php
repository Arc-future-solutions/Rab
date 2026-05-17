<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HighPriorityDiagnosticAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $lead,
        public array $results,
        public array $context,
        public array $insightAreas
    ) {
    }

    public function envelope(): Envelope
    {
        $assessmentType = strtoupper((string) ($this->results['type'] ?? 'PIR'));
        $company = $this->lead['company'] ?? 'Unknown company';

        return new Envelope(
            subject: "High-Priority {$assessmentType} Diagnostic — {$company}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.high-priority-diagnostic-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
