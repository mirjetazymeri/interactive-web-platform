<?php

namespace App\Services\Meetings\Calendar;

use App\Enums\MeetingFormat;
use App\Models\MeetingBooking;
use App\Models\MeetingCalendarConnection;
use App\Services\Meetings\MeetingSettings;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mirrors a booking into the host's Microsoft 365 calendar: creates, updates
 * or deletes its event, and creates the Teams meeting link for online
 * bookings that use automatic links.
 *
 * Events carry no attendees, so Outlook sends the visitor nothing; our own
 * email and .ics stay the only invitation. A Teams link that can't be
 * created falls back to the provider's static URL when one is configured,
 * otherwise it stays unset ("failed") so no email carries a made-up link.
 */
class CalendarSyncService
{
    public function __construct(private MicrosoftCalendarGateway $calendar, private MeetingSettings $settings) {}

    /**
     * $formatChanged replaces the event instead of patching it, so a Teams
     * meeting is never left on an in-person event (or missing from an online one).
     *
     * @return array{link_ready_now: bool, link_failed: bool}
     */
    public function sync(MeetingBooking $booking, bool $formatChanged = false): array
    {
        $booking->loadMissing('meetingType');
        $wasReady = $booking->video_link_status === 'ready';
        $wantsTeams = $this->wantsTeams($booking);
        $connection = $this->calendar->connection((int) $booking->channel_id);

        if (! $connection?->isConnected()) {
            if ($wantsTeams) {
                $this->teamsFailed($booking, 'The Microsoft 365 calendar is not connected.');
            }
            $booking->update(['calendar_sync_status' => 'not_synced']);

            return $this->result($booking, $wasReady);
        }

        try {
            if ($booking->calendar_event_id && ($booking->status === 'cancelled' || $formatChanged)) {
                $this->calendar->deleteEvent($connection, $booking->calendar_event_id);
                $booking->update(['calendar_event_id' => null]);
            }

            if ($booking->status !== 'cancelled') {
                $event = $booking->calendar_event_id
                    ? $this->calendar->patchEvent($connection, $booking->calendar_event_id, $this->eventBody($booking, $wantsTeams))
                    : $this->calendar->createEvent($connection, $this->eventBody($booking, $wantsTeams) + [
                        // Lets Microsoft drop a duplicate if a create is retried after a timeout.
                        'transactionId' => $booking->reference.'-'.Str::lower(Str::random(6)),
                    ]);
                $booking->update(['calendar_event_id' => $event['id'], 'calendar_id' => $connection->calendar_id]);

                if ($wantsTeams) {
                    $this->resolveTeams($booking, $connection, $event);
                }
            }
            $booking->update(['calendar_sync_status' => 'synced', 'calendar_sync_error' => null]);
            $this->log($booking, 'calendar_synced');
        } catch (Throwable $e) {
            $booking->update(['calendar_sync_status' => 'failed', 'calendar_sync_error' => Str::limit($e->getMessage(), 1000)]);
            $this->log($booking, 'calendar_failed', ['error' => Str::limit($e->getMessage(), 300)]);
            if ($wantsTeams && $booking->video_link_status === 'pending') {
                $this->teamsFailed($booking, $e->getMessage());
            }
        }

        $this->calendar->forget((int) $booking->channel_id);

        return $this->result($booking, $wasReady);
    }

    /** Online bookings with automatic links that don't have one yet. */
    private function wantsTeams(MeetingBooking $booking): bool
    {
        return $booking->status !== 'cancelled' && $booking->format === MeetingFormat::Online
            && $booking->video_link_source === 'auto' && in_array($booking->video_link_status, ['pending', 'failed'], true);
    }

    private function eventBody(MeetingBooking $booking, bool $wantsTeams): array
    {
        $online = $booking->format === MeetingFormat::Online;
        $snapshot = (array) $booking->location_snapshot;
        $locale = $this->settings->defaultLocale();
        // Teams shows its own join details; other online links go in the location.
        $where = $online
            ? ($booking->video_link_status === 'ready' && $booking->video_link_source !== 'auto' ? $booking->video_url : '')
            : ($snapshot['address'] ?? '');

        $body = [
            'subject' => $booking->meetingType->translated('name', $locale).' — '.$booking->name,
            'body' => ['contentType' => 'text', 'content' => implode("\n", array_filter([
                __('meetings.format', [], $locale).': '.$booking->format->label(),
                $booking->name.' <'.$booking->email.'>',
                $booking->phone,
                $booking->company,
                $booking->project_details ? "\n".$booking->project_details."\n" : null,
                $online && $booking->video_link_status === 'ready' && $booking->video_link_source !== 'auto' ? $booking->video_url : null,
                ! $online ? ($snapshot['maps_url'] ?? null) : null,
                __('meetings.mail.reference', [], $locale).': '.$booking->reference,
                route('admin.meetings.bookings.show', $booking->id),
            ]))],
            'start' => ['dateTime' => $booking->starts_at->copy()->utc()->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC'],
            'end' => ['dateTime' => $booking->ends_at->copy()->utc()->format('Y-m-d\TH:i:s'), 'timeZone' => 'UTC'],
            'location' => ['displayName' => (string) $where],
            'showAs' => 'busy',
        ];

        if ($wantsTeams) {
            $body['isOnlineMeeting'] = true;
            $body['onlineMeetingProvider'] = 'teamsForBusiness';
        }

        return $body;
    }

    /** Read the Teams join link, asking again while Microsoft is still creating it. */
    private function resolveTeams(MeetingBooking $booking, MeetingCalendarConnection $connection, array $event): void
    {
        $delays = (array) config('meetings.microsoft.teams_poll_seconds', []);

        for ($attempt = 0; ; $attempt++) {
            $link = data_get($event, 'onlineMeeting.joinUrl');

            if ($link && filter_var($link, FILTER_VALIDATE_URL)) {
                $booking->update(['video_url' => $link, 'video_link_status' => 'ready', 'video_provider' => 'teams']);

                return;
            }
            if ($attempt >= count($delays)) {
                $this->teamsFailed($booking, 'Microsoft did not return a Teams join link.');

                return;
            }
            sleep((int) $delays[$attempt]);
            $event = $this->calendar->getEvent($connection, $event['id']);
        }
    }

    /** Fall back to a configured static URL, otherwise leave the link unset for an admin to add. */
    private function teamsFailed(MeetingBooking $booking, string $reason): void
    {
        $static = trim((string) ($this->settings->array('online.static_urls', (int) $booking->channel_id)[$booking->video_provider] ?? ''));

        $booking->update($static !== ''
            ? ['video_url' => $static, 'video_link_status' => 'ready', 'video_link_source' => 'static']
            : ['video_url' => null, 'video_link_status' => 'failed']);
        $this->log($booking, 'teams_failed', ['error' => Str::limit($reason, 300), 'fallback' => $static !== '']);
    }

    private function result(MeetingBooking $booking, bool $wasReady): array
    {
        return [
            'link_ready_now' => ! $wasReady && $booking->video_link_status === 'ready',
            'link_failed' => $booking->video_link_status === 'failed',
        ];
    }

    private function log(MeetingBooking $booking, string $type, array $payload = []): void
    {
        $booking->events()->create(['type' => $type, 'payload' => $payload ?: null]);
    }
}
