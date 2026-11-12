<?php

namespace App\Mail\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserMail extends Mailable
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
            to: [setting('notifications.admin.email', config('mail.from.address'))],
            subject: '[Admin] New User Registration: ' . $this->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin.new-user');
    }

    public function attachments(): array { return []; }
}
