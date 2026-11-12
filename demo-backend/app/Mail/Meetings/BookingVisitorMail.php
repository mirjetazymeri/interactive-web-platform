<?php

namespace App\Mail\Meetings;

use App\Enums\MeetingFormat;
use App\Models\MeetingBooking;
use App\Services\Meetings\IcsBuilder;
use App\Services\Meetings\MeetingLocationPresenter;
use App\Services\Meetings\MeetingSettings;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Every email the visitor gets about a booking. $kind is one of KINDS; the
 * location block comes from MeetingLocationPresenter, which never hands out
 * a video URL that is not ready.
 */
class BookingVisitorMail extends Mailable
{
    use Queueable, SerializesModels;

    public const KINDS = ['confirmed', 'requested', 'updated', 'video_link', 'cancelled'];

    /** @param  array{format?: string, starts_at?: string}  $previous  the booking before an update */
    public function __construct(public MeetingBooking $booking, public string $kind, public array $previous = [])
    {
        $this->locale($booking->locale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                setting('notifications.mail.from_address', config('mail.from.address')),
                setting('notifications.mail.from_name', config('mail.from.name')),
            ),
            subject: __('meetings.mail.subject_'.$this->kind, [
                'type' => $this->booking->meetingType->translated('name', $this->booking->locale),
                'date' => $this->localTime($this->booking->starts_at)->isoFormat('LL'),
            ]),
        );
    }

    public function content(): Content
    {
        $booking = $this->booking;

        return new Content(view: 'emails.meetings.visitor', with: [
            'typeName' => $booking->meetingType->translated('name', $booking->locale),
            'when' => $this->localTime($booking->starts_at),
            'until' => $this->localTime($booking->ends_at),
            'businessTimezone' => app(MeetingSettings::class)->timezone((int) $booking->channel_id),
            'location' => app(MeetingLocationPresenter::class)->present($booking),
            'previousWhen' => isset($this->previous['starts_at']) ? $this->localTime(CarbonImmutable::parse($this->previous['starts_at'])) : null,
            'previousFormat' => isset($this->previous['format']) ? MeetingFormat::from($this->previous['format']) : null,
        ]);
    }

    /** A calendar file for every email except "request received", which isn't in anyone's calendar yet. */
    public function attachments(): array
    {
        if ($this->kind === 'requested') {
            return [];
        }

        $cancel = $this->kind === 'cancelled';

        return [
            Attachment::fromData(fn () => app(IcsBuilder::class)->build($this->booking, $cancel), 'meeting.ics')
                ->withMime('text/calendar; charset=UTF-8; method='.($cancel ? 'CANCEL' : 'REQUEST')),
        ];
    }

    private function localTime($time): CarbonImmutable
    {
        return CarbonImmutable::instance($time)->setTimezone($this->booking->visitor_timezone)->locale($this->booking->locale);
    }
}
