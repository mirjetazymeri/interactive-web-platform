<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $user,
        public string $verificationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                setting('notifications.mail.from_address', config('mail.from.address')),
                setting('notifications.mail.from_name',    config('mail.from.name')),
            ),
            subject: 'Verify your email address',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.user.verify-email');
    }

    public function attachments(): array { return []; }
}
