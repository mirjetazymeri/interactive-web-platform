<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $formSubject;
    public string $formMessage;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        string $subject,
        string $message,
    ) {
        $this->formSubject = $subject;
        $this->formMessage = $message;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                setting('notifications.mail.from_address', config('mail.from.address')),
                setting('notifications.mail.from_name',    config('mail.from.name')),
            ),
            to: [setting('notifications.admin.email', config('mail.from.address'))],
            replyTo: [new \Illuminate\Mail\Mailables\Address($this->senderEmail, $this->senderName)],
            subject: '[Contact] ' . $this->formSubject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin.contact-form');
    }

    public function attachments(): array { return []; }
}
