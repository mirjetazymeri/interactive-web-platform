<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Mail\MailService;

class UserObserver
{
    public function __construct(protected MailService $mail) {}

    /** Fired after a new User row is inserted. */
    public function created(User $user): void
    {
        // Welcome email to the customer
        $this->mail->sendWelcome($user);

        // Admin notification about the new registration
        $this->mail->notifyAdminNewUser($user);
    }
}
