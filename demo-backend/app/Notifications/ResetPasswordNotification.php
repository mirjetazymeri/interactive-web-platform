<?php

namespace App\Notifications;

use App\Mail\PasswordResetMail;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    /**
     * Return our custom mailable for Laravel's MailChannel to deliver.
     */
    public function toMail(mixed $notifiable): Mailable
    {
        $resetUrl = $this->resetUrl($notifiable);

        return (new PasswordResetMail($notifiable, $resetUrl))
            ->to($notifiable->email, $notifiable->name ?? null);
    }
}
