<?php

namespace App\Services\Mail;

use App\Mail\Admin\ContactFormMail;
use App\Mail\Admin\NewUserMail;
use App\Mail\LoginAlertMail;
use App\Mail\Meetings\BookingAdminMail;
use App\Mail\Meetings\BookingVisitorMail;
use App\Models\MeetingBooking;
use App\Services\Meetings\MeetingSettings;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Central mail dispatch service.
 */
class MailService
{
    /* ──────────────── helpers ──────────────────────────────────────── */

    /** Resolve the admin notification address from settings. */
    protected function adminEmail(): string
    {
        return (string) (setting('notifications.admin.email') ?: config('mail.from.address'));
    }

    /** Safely dispatch a mailable, logging any exception. */
    protected function send(string $to, \Illuminate\Mail\Mailable $mailable): void
    {
        try {
            Mail::to($to)->queue($mailable);
        } catch (\Throwable $e) {
            Log::error('[MailService] Failed to dispatch ' . get_class($mailable) . ': ' . $e->getMessage());
        }
    }

    /* ──────────────── User notifications ──────────────────────────── */

    /** Send welcome email to a newly registered user. */
    public function sendWelcome(User $user): void
    {
        if (! setting('notifications.user.welcome_email')) {
            return;
        }
        $this->send($user->email, new WelcomeMail($user));
    }

    /** Send login alert to a user. */
    public function sendLoginAlert(User $user, string $ip, string $userAgent): void
    {
        if (! setting('notifications.user.login_alert')) {
            return;
        }
        $this->send($user->email, new LoginAlertMail(
            user:      $user,
            ip:        $ip,
            userAgent: $userAgent,
            loginAt:   now()->format('d M Y, H:i') . ' UTC',
        ));
    }

    /* ──────────────── Admin notifications ─────────────────────────── */

    /** Notify admin about a new user registration. */
    public function notifyAdminNewUser(User $user): void
    {
        if (! setting('notifications.admin.notify_new_user')) {
            return;
        }
        try {
            Mail::to($this->adminEmail())->queue(new NewUserMail($user));
        } catch (\Throwable $e) {
            Log::error('[MailService] Admin new-user mail failed: ' . $e->getMessage());
        }
    }

    /* ──────────────── Meetings ────────────────────────────────────── */

    /**
     * Send a booking email to the visitor. Returns whether it was handed to
     * the mailer, so the booking timeline can record failures.
     */
    public function sendMeetingMail(MeetingBooking $booking, string $kind, array $previous = []): bool
    {
        try {
            Mail::to($booking->email, $booking->name)->queue(new BookingVisitorMail($booking, $kind, $previous));

            return true;
        } catch (\Throwable $e) {
            Log::error('[MailService] Meeting mail failed: ' . $e->getMessage());

            return false;
        }
    }

    /** Tell the booking recipients (or the admin address) about a new, changed or cancelled booking. */
    public function notifyAdminMeeting(MeetingBooking $booking, string $kind): void
    {
        $recipients = app(MeetingSettings::class)->array('general.admin_recipients', (int) $booking->channel_id);

        try {
            Mail::to($recipients ?: [$this->adminEmail()])->queue(new BookingAdminMail($booking, $kind));
        } catch (\Throwable $e) {
            Log::error('[MailService] Admin meeting mail failed: ' . $e->getMessage());
        }
    }

    /**
     * Forward a contact-form submission to admin inbox.
     */
    public function forwardContactForm(
        string $senderName,
        string $senderEmail,
        string $subject,
        string $message,
    ): void {
        if (! setting('notifications.admin.notify_contact_form')) {
            return;
        }
        try {
            Mail::to($this->adminEmail())->queue(
                new ContactFormMail($senderName, $senderEmail, $subject, $message)
            );
        } catch (\Throwable $e) {
            Log::error('[MailService] Contact form mail failed: ' . $e->getMessage());
        }
    }
}
