<?php

namespace App\Services\Meetings;

use App\Models\MeetingBooking;
use Carbon\CarbonInterface;

/** iCalendar (RFC 5545) invitations and cancellations for a booking. */
class IcsBuilder
{
    public function __construct(private MeetingLocationPresenter $presenter, private MeetingSettings $settings) {}

    public function build(MeetingBooking $booking, bool $cancel = false): string
    {
        $location = $this->presenter->present($booking);
        $where = $location['kind'] === 'online' ? ($location['url'] ?? '') : ($location['address'] ?? '');
        $description = implode("\n\n", array_filter([
            $location['kind'] === 'online' ? $location['url'] : $location['maps_url'],
            ...$location['instructions'],
        ]));
        $organizer = (string) ($this->settings->get('general.organizer_name', (int) $booking->channel_id) ?: config('mail.from.name'));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.$this->escape((string) config('app.name')).'//Meetings//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:'.($cancel ? 'CANCEL' : 'REQUEST'),
            'BEGIN:VEVENT',
            'UID:'.$booking->reference.'@'.(parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'),
            'SEQUENCE:'.$this->sequence($booking),
            'DTSTAMP:'.$this->time(now()),
            'DTSTART:'.$this->time($booking->starts_at),
            'DTEND:'.$this->time($booking->ends_at),
            'SUMMARY:'.$this->escape((string) $booking->meetingType->translated('name', $booking->locale)),
            'STATUS:'.($cancel ? 'CANCELLED' : 'CONFIRMED'),
            'ORGANIZER;CN='.$this->escape($organizer).':mailto:'.config('mail.from.address'),
            'ATTENDEE;CN='.$this->escape($booking->name).';ROLE=REQ-PARTICIPANT:mailto:'.$booking->email,
        ];
        if ($where !== '') {
            $lines[] = 'LOCATION:'.$this->escape($where);
        }
        if ($location['kind'] === 'online' && $location['url']) {
            $lines[] = 'URL:'.$location['url'];
        }
        if ($description !== '') {
            $lines[] = 'DESCRIPTION:'.$this->escape($description);
        }
        array_push($lines, 'END:VEVENT', 'END:VCALENDAR');

        return implode("\r\n", array_map(fn ($line) => $this->fold($line), $lines))."\r\n";
    }

    /** Each change to an invitation must raise SEQUENCE so calendars replace the old one. */
    private function sequence(MeetingBooking $booking): int
    {
        return $booking->events()->whereIn('type', ['format_changed', 'rescheduled', 'cancelled'])->count();
    }

    private function time(CarbonInterface $time): string
    {
        return $time->copy()->utc()->format('Ymd\THis\Z');
    }

    private function escape(string $text): string
    {
        return str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $text);
    }

    /** Lines longer than 75 octets continue on the next line after a space. */
    private function fold(string $line): string
    {
        $parts = [];
        while (strlen($line) > 75) {
            $cut = 75;
            // Don't split a multi-byte character.
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $parts[] = substr($line, 0, $cut);
            $line = ' '.substr($line, $cut);
        }
        $parts[] = $line;

        return implode("\r\n", $parts);
    }
}
