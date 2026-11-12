<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $user,
        public string $ip,
        public string $userAgent,
        public string $loginAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                setting('notifications.mail.from_address', config('mail.from.address')),
                setting('notifications.mail.from_name',    config('mail.from.name')),
            ),
            subject: 'New login to your account',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.user.login-alert');
    }

    public function attachments(): array { return []; }
}
