<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PendingCongesAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $count,
        public Collection $demandesByAgence,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->count > 1
            ? $this->count . ' demandes de congé en attente de traitement'
            : '1 demande de congé en attente de traitement';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.conges-pending',
        );
    }
}
