<?php

namespace App\Notifications;

use App\Mail\VerifyEmailMail;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class VerifyEmailNotification extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Return our custom mailable for Laravel's MailChannel to deliver.
     */
    public function toMail(mixed $notifiable): Mailable
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new VerifyEmailMail($notifiable, $verificationUrl))
            ->to($notifiable->email, $notifiable->name ?? null);
    }
}
