<?php

namespace App\Services\Meetings;

use App\Enums\MeetingFormat;
use App\Enums\VideoProvider;
use App\Models\MeetingBooking;
use App\Models\MeetingCalendarConnection;
use App\Models\MeetingType;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\Mail\MailService;
use App\Services\Meetings\Calendar\CalendarSyncService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Illuminate\Support\defer;

/**
 * Creates and changes bookings. Every write that takes calendar time runs
 * under a per-channel lock and re-checks availability inside it, so two
 * requests can never book the same time. Emails go out after the response.
 */
class BookingService
{
    public function __construct(
        private AvailabilityService $availability,
        private MeetingTypeService $types,
        private MeetingSettings $settings,
        private MeetingLocationPresenter $presenter,
        private MailService $mail,
        private CalendarSyncService $calendar,
    ) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, company?: ?string, project_details?: ?string}  $visitor
     */
    public function create(MeetingType $type, MeetingFormat $format, CarbonInterface $start, array $visitor, string $locale, string $visitorTimezone): MeetingBooking
    {
        if (! $type->active) {
            throw new BookingException(BookingException::TYPE_UNAVAILABLE);
        }
        $office = $this->officeFor($type, $format);
        $channelId = (int) $type->channel_id;
        $start = CarbonImmutable::instance($start)->utc();

        $booking = $this->locked($channelId, function () use ($type, $format, $start, $office, $visitor, $locale, $visitorTimezone, $channelId) {
            if (! $this->availability->isAvailable($type, $format, $start)) {
                throw new BookingException(BookingException::SLOT_TAKEN);
            }
            $approval = $this->settings->bool('general.require_approval', $channelId);

            return MeetingBooking::create([
                'channel_id' => $channelId,
                'meeting_type_id' => $type->id,
                'status' => $approval ? 'pending_approval' : 'confirmed',
                'confirmed_at' => $approval ? null : now(),
                'visitor_timezone' => $visitorTimezone,
                'locale' => $locale,
                'name' => $visitor['name'],
                'email' => $visitor['email'],
                'phone' => $visitor['phone'] ?? null,
                'company' => $visitor['company'] ?? null,
                'project_details' => $visitor['project_details'] ?? null,
                ...$this->placement($type, $format, $start, $office, $locale),
            ]);
        });

        $this->log($booking, 'created', ['status' => $booking->status, 'format' => $format->value, 'starts_at' => $start->toIso8601String()]);
        defer(function () use ($booking) {
            // Calendar first, so the confirmation can carry a freshly created Teams link.
            $this->calendar->sync($booking);
            $this->notifyVisitor($booking, $booking->status === 'pending_approval' ? 'requested' : 'confirmed');
            $this->mail->notifyAdminMeeting($booking, 'new');
        });

        return $booking;
    }

    /** Move a booking to another format (and optionally another time or office), re-checking availability. */
    public function changeFormat(MeetingBooking $booking, MeetingFormat $format, ?CarbonInterface $start = null, ?int $officeId = null, bool $notify = true, ?User $by = null): MeetingBooking
    {
        return $this->move($booking, $format, $start ?? $booking->starts_at, $officeId, $notify, $by, 'format_changed');
    }

    public function reschedule(MeetingBooking $booking, CarbonInterface $start, bool $notify = true, ?User $by = null): MeetingBooking
    {
        return $this->move($booking, $booking->format, $start, $booking->office_location_id, $notify, $by, 'rescheduled');
    }

    public function cancel(MeetingBooking $booking, ?string $reason = null, bool $notify = true, ?User $by = null): MeetingBooking
    {
        $this->assertChangeable($booking);
        $booking->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancel_reason' => $reason, 'cancelled_by' => $by?->id]);
        $this->log($booking, 'cancelled', ['reason' => $reason, 'notified' => $notify], $by);

        defer(function () use ($booking, $notify) {
            $this->calendar->sync($booking);
            if ($notify) {
                $this->notifyVisitor($booking, 'cancelled');
            }
            $this->mail->notifyAdminMeeting($booking, 'cancelled');
        });

        return $booking;
    }

    /** Confirm a booking that was waiting for approval. Its time is already held. */
    public function confirm(MeetingBooking $booking, ?User $by = null): MeetingBooking
    {
        if ($booking->status !== 'pending_approval') {
            throw new BookingException(BookingException::NOT_CHANGEABLE);
        }
        $booking->update(['status' => 'confirmed', 'confirmed_at' => now()]);
        $this->log($booking, 'confirmed', [], $by);
        defer(fn () => $this->notifyVisitor($booking, 'confirmed'));

        return $booking;
    }

    /** Set the real video link of an online booking and send it to the visitor. */
    public function setVideoLink(MeetingBooking $booking, string $url, ?User $by = null): MeetingBooking
    {
        $this->assertChangeable($booking);
        if ($booking->format !== MeetingFormat::Online) {
            throw new BookingException(BookingException::FORMAT_UNAVAILABLE);
        }
        $booking->update(['video_url' => $url, 'video_link_status' => 'ready', 'video_link_source' => 'manual']);
        $this->log($booking, 'video_link_set', ['url' => $url], $by);
        defer(function () use ($booking) {
            $this->calendar->sync($booking);
            $this->notifyVisitor($booking, 'video_link');
        });

        return $booking;
    }

    /** Sync the calendar event again (after a failure); emails the link if it only now became ready. */
    public function retryCalendarSync(MeetingBooking $booking, ?User $by = null): MeetingBooking
    {
        $this->assertChangeable($booking);
        $this->log($booking, 'calendar_retry', [], $by);
        if ($this->calendar->sync($booking)['link_ready_now']) {
            defer(fn () => $this->notifyVisitor($booking, 'video_link'));
        }

        return $booking;
    }

    public function resendConfirmation(MeetingBooking $booking, ?User $by = null): void
    {
        $this->assertChangeable($booking);
        $this->log($booking, 'confirmation_resent', [], $by);
        defer(fn () => $this->notifyVisitor($booking, $booking->status === 'pending_approval' ? 'requested' : 'confirmed'));
    }

    private function move(MeetingBooking $booking, MeetingFormat $format, CarbonInterface $start, ?int $officeId, bool $notify, ?User $by, string $event): MeetingBooking
    {
        $this->assertChangeable($booking);
        $type = $booking->meetingType;
        $office = $this->officeFor($type, $format, $officeId);
        $start = CarbonImmutable::instance($start)->utc();
        $before = $this->summary($booking);

        $this->locked((int) $booking->channel_id, function () use ($booking, $type, $format, $start, $office) {
            if (! $this->availability->isAvailable($type, $format, $start, $booking)) {
                throw new BookingException(BookingException::SLOT_TAKEN);
            }
            $placement = $this->placement($type, $format, $start, $office, $booking->locale);
            // Keep a link that is still valid for the same format.
            if ($booking->format === $format && $format === MeetingFormat::Online) {
                $placement = array_diff_key($placement, array_flip(['video_provider', 'video_url', 'video_link_source', 'video_link_status']));
            }
            $booking->update($placement);
        });

        $this->log($booking, $event, ['from' => $before, 'to' => $this->summary($booking), 'notified' => $notify], $by);
        defer(function () use ($booking, $before, $notify) {
            $this->calendar->sync($booking, $before['format'] !== $booking->format->value);
            if ($notify) {
                $this->notifyVisitor($booking, 'updated', $before);
            }
            $this->mail->notifyAdminMeeting($booking, 'updated');
        });

        return $booking;
    }

    /** Times, office and video fields for a booking of this type, format and start. */
    private function placement(MeetingType $type, MeetingFormat $format, CarbonImmutable $start, ?OfficeLocation $office, string $locale): array
    {
        ['duration' => $duration, 'buffer_before' => $before, 'buffer_after' => $after] = $type->settingsFor($format);
        $end = $start->addMinutes($duration);

        return [
            'format' => $format,
            'starts_at' => $start,
            'ends_at' => $end,
            'blocked_from' => $start->subMinutes($before),
            'blocked_until' => $end->addMinutes($after),
            'office_location_id' => $office?->id,
            'location_snapshot' => $office ? $this->presenter->snapshot($office, $locale) : null,
            ...$this->videoLink($type, $format),
        ];
    }

    /**
     * Teams links are created with the calendar event when it is connected;
     * otherwise a configured static URL is ready at once, and anything else
     * waits for an admin to add one.
     */
    private function videoLink(MeetingType $type, MeetingFormat $format): array
    {
        if ($format !== MeetingFormat::Online) {
            return ['video_provider' => null, 'video_url' => null, 'video_link_source' => null, 'video_link_status' => 'not_applicable'];
        }

        $channelId = (int) $type->channel_id;
        $online = $this->settings->online($channelId);
        $provider = $type->video_provider ?? VideoProvider::tryFrom($online['provider']) ?? VideoProvider::Teams;
        $static = trim((string) ($online['static_urls'][$provider->value] ?? ''));
        $auto = $provider->supportsAutoCreate() && $online['auto_create']
            && MeetingCalendarConnection::query()->where('channel_id', $channelId)->first()?->isConnected();

        // A Teams meeting per booking wins; the static URL is then only the fallback if creating it fails.
        if ($auto) {
            return ['video_provider' => $provider->value, 'video_url' => null, 'video_link_source' => 'auto', 'video_link_status' => 'pending'];
        }
        if ($static !== '') {
            return ['video_provider' => $provider->value, 'video_url' => $static, 'video_link_source' => 'static', 'video_link_status' => 'ready'];
        }

        return ['video_provider' => $provider->value, 'video_url' => null, 'video_link_source' => 'manual', 'video_link_status' => 'pending'];
    }

    private function officeFor(MeetingType $type, MeetingFormat $format, ?int $officeId = null): ?OfficeLocation
    {
        if (! $type->allows($format)) {
            throw new BookingException(BookingException::FORMAT_UNAVAILABLE);
        }
        if ($format !== MeetingFormat::InPerson) {
            return null;
        }

        $office = $officeId
            ? OfficeLocation::query()->forChannel((int) $type->channel_id)->where('active', true)->find($officeId)
            : $this->types->officeFor($type);

        if (! $office?->hasCompleteAddress()) {
            throw new BookingException(BookingException::OFFICE_UNAVAILABLE);
        }

        return $office;
    }

    private function assertChangeable(MeetingBooking $booking): void
    {
        if (! in_array($booking->status, MeetingBooking::BLOCKING_STATUSES, true)) {
            throw new BookingException(BookingException::NOT_CHANGEABLE);
        }
    }

    /** Run $callback holding the channel's booking lock, in a transaction. */
    private function locked(int $channelId, callable $callback): mixed
    {
        return Cache::lock("meetings:book:{$channelId}", 15)->block(10, fn () => DB::transaction($callback));
    }

    private function summary(MeetingBooking $booking): array
    {
        return [
            'format' => $booking->format->value,
            'starts_at' => $booking->starts_at->toIso8601String(),
            'office_location_id' => $booking->office_location_id,
        ];
    }

    private function notifyVisitor(MeetingBooking $booking, string $kind, array $previous = []): void
    {
        $sent = $this->mail->sendMeetingMail($booking->fresh(), $kind, $previous);
        $this->log($booking, $sent ? 'mail_sent' : 'mail_failed', ['kind' => $kind]);
    }

    private function log(MeetingBooking $booking, string $type, array $payload = [], ?User $by = null): void
    {
        $booking->events()->create(['type' => $type, 'payload' => $payload ?: null, 'user_id' => $by?->id]);
    }
}
