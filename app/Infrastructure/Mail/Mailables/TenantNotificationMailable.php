<?php

namespace App\Infrastructure\Mail\Mailables;

use App\Application\Mail\DTOs\EmailDTO;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TenantNotificationMailable extends Mailable
{
    public function __construct(
        private readonly EmailDTO $dto,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->dto->subject);
    }

    public function content(): Content
    {
        return new Content(
            view: $this->dto->template,
            with: $this->dto->data,
        );
    }
}
