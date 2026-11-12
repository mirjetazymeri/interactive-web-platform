<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                setting('notifications.mail.from_address', config('mail.from.address')),
                setting('notifications.mail.from_name',    config('mail.from.name')),
            ),
            subject: 'Welcome to ' . setting('store.company_name', config('app.name')) . '!',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.user.welcome');
    }

    public function attachments(): array { return []; }
}
