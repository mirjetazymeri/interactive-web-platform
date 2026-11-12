<?php

namespace App\Mail\Meetings;

use App\Models\MeetingBooking;
use App\Services\Meetings\MeetingLocationPresenter;
use App\Services\Meetings\MeetingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Booking notifications for the team: new, updated or cancelled. Flags bookings that still need a video link. */
class BookingAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public const KINDS = ['new', 'updated', 'cancelled'];

    public function __construct(public MeetingBooking $booking, public string $kind) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                setting('notifications.mail.from_address', config('mail.from.address')),
                setting('notifications.mail.from_name', config('mail.from.name')),
            ),
            replyTo: [new Address($this->booking->email, $this->booking->name)],
            subject: __('meetings.mail.admin_subject_'.$this->kind, [
                'name' => $this->booking->name,
                'date' => $this->businessTime($this->booking->starts_at)->isoFormat('LLL'),
            ]),
        );
    }

    public function content(): Content
    {
        $location = app(MeetingLocationPresenter::class)->present($this->booking);

        return new Content(view: 'emails.meetings.admin', with: [
            'typeName' => $this->booking->meetingType->translated('name', app()->getLocale()),
            'when' => $this->businessTime($this->booking->starts_at),
            'until' => $this->businessTime($this->booking->ends_at),
            'timezone' => $this->timezone(),
            'location' => $location,
            'needsLink' => $this->kind !== 'cancelled' && $location['kind'] === 'online' && ! $location['link_ready']
                && ($this->booking->video_link_source === 'manual' || $this->booking->video_link_status === 'failed'),
            'url' => route('admin.meetings.bookings.show', $this->booking->id),
        ]);
    }

    private function businessTime($time): CarbonImmutable
    {
        return CarbonImmutable::instance($time)->setTimezone($this->timezone())->locale(app()->getLocale());
    }

    private function timezone(): string
    {
        return app(MeetingSettings::class)->timezone((int) $this->booking->channel_id);
    }
}
