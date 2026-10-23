<?php

namespace App\Modules\Notification\Mail;

use App\Modules\Notification\Models\LogLetter;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A plain-text letter rendered from a template (`log_letters` row).
 */
class LetterMail extends Mailable
{
    public function __construct(
        public readonly LogLetter $letter,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                $this->letter->sender ?? config('mail.from.address'),
                $this->letter->sender_name ?? config('mail.from.name'),
            ),
            subject: $this->letter->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.letter',
            with: ['body' => $this->letter->body],
        );
    }
}
