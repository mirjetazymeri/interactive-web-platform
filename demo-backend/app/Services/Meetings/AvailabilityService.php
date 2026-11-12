<?php

namespace App\Services\Meetings;

use App\Enums\MeetingFormat;
use App\Models\MeetingAvailabilityException;
use App\Models\MeetingAvailabilityRule;
use App\Models\MeetingBooking;
use App\Models\MeetingType;
use App\Services\Meetings\Calendar\CalendarGateway;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Bookable start times for a meeting type and format.
 *
 * Weekly rules and exceptions are wall-clock times in the business timezone.
 * Every format and type is checked against the same busy times (stored
 * bookings and the host calendar), so formats can never double-book the host.
 */
class AvailabilityService
{
    public function __construct(private MeetingSettings $settings, private CalendarGateway $calendar) {}

    /**
     * Slots starting in [$from, $to), in UTC and in time order.
     * $ignore leaves a booking out of the busy times (when moving it).
     *
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    public function slots(MeetingType $type, MeetingFormat $format, CarbonInterface $from, CarbonInterface $to, ?MeetingBooking $ignore = null, bool $fresh = false): array
    {
        if (! $type->allows($format)) {
            throw new InvalidArgumentException("Meeting type {$type->id} does not offer the {$format->value} format.");
        }

        $channelId = (int) $type->channel_id;
        $timezone = $this->settings->timezone($channelId);
        ['duration' => $duration, 'buffer_before' => $before, 'buffer_after' => $after] = $type->settingsFor($format);

        $now = CarbonImmutable::now('UTC');
        $earliest = max(CarbonImmutable::instance($from)->utc(), $now->addMinutes((int) $type->min_notice_minutes));
        $latest = min(
            CarbonImmutable::instance($to)->utc(),
            $now->setTimezone($timezone)->startOfDay()->addDays((int) $type->max_advance_days + 1)->utc(),
        );
        if ($earliest->gte($latest)) {
            return [];
        }

        $rules = $this->weeklyRules($type, $format);
        $firstDay = $earliest->setTimezone($timezone)->startOfDay();
        $lastDay = $latest->setTimezone($timezone)->startOfDay();
        $exceptions = $this->exceptions($type, $format, $firstDay, $lastDay);
        $busy = $this->busy($channelId, $earliest->subMinutes($before), $latest->addMinutes($duration + $after), $ignore, $fresh);

        $slots = [];
        for ($day = $firstDay; $day->lte($lastDay); $day = $day->addDay()) {
            $date = $day->toDateString();
            $windows = $this->windows($rules[$day->dayOfWeek] ?? [], $exceptions->get($date, collect()));

            foreach ($windows as [$open, $close]) {
                for ($minute = $open; $minute + $duration <= $close; $minute += (int) $type->slot_interval_minutes) {
                    $start = $this->localTime($date, $minute, $timezone);
                    if (! $start || $start->lt($earliest) || $start->gte($latest)) {
                        continue;
                    }
                    $end = $start->addMinutes($duration);
                    if ($this->clashes($start, $end, $start->subMinutes($before), $end->addMinutes($after), $busy)) {
                        continue;
                    }
                    $slots[$start->getTimestamp()] = ['start' => $start, 'end' => $end];
                }
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /** Whether a booking of this type and format can start exactly at $start. */
    public function isAvailable(MeetingType $type, MeetingFormat $format, CarbonInterface $start, ?MeetingBooking $ignore = null): bool
    {
        $start = CarbonImmutable::instance($start)->utc();

        // A live calendar check: this guards the final write.
        return collect($this->slots($type, $format, $start, $start->addMinute(), $ignore, true))
            ->contains(fn (array $slot) => $slot['start']->eq($start));
    }

    /**
     * The rule scope that applies to a type and format: the most specific one
     * with any hours, as [meeting_type_id|null, format|null], or null if none.
     *
     * @return array{0: ?int, 1: ?MeetingFormat}|null
     */
    public function effectiveScope(MeetingType $type, MeetingFormat $format): ?array
    {
        $rules = $this->candidateRules((int) $type->channel_id, $type->id, $format);

        foreach ($this->scopes($type->id, $format) as $scope) {
            if ($this->inScope($rules, ...$scope)->isNotEmpty()) {
                return $scope;
            }
        }

        return null;
    }

    /**
     * Opening windows per weekday (0 = Sunday) in minutes after midnight,
     * from the most specific scope that has any rules.
     *
     * @return array<int, list<array{0: int, 1: int}>>
     */
    private function weeklyRules(MeetingType $type, MeetingFormat $format): array
    {
        $scope = $this->effectiveScope($type, $format);
        if (! $scope) {
            return [];
        }

        return $this->inScope($this->candidateRules((int) $type->channel_id, $type->id, $format), ...$scope)
            ->groupBy('weekday')
            ->map(fn (Collection $day) => $day->map(fn ($rule) => [self::minutes($rule->start_time), self::minutes($rule->end_time)])->values()->all())
            ->all();
    }

    private function candidateRules(int $channelId, int $typeId, MeetingFormat $format): Collection
    {
        return MeetingAvailabilityRule::query()->where('channel_id', $channelId)
            ->where(fn ($q) => $q->whereNull('meeting_type_id')->orWhere('meeting_type_id', $typeId))
            ->where(fn ($q) => $q->whereNull('format')->orWhere('format', $format->value))
            ->get();
    }

    /** Scopes from most to least specific. */
    private function scopes(int $typeId, MeetingFormat $format): array
    {
        return [[$typeId, $format], [$typeId, null], [null, $format], [null, null]];
    }

    private function inScope(Collection $rules, ?int $typeId, ?MeetingFormat $format): Collection
    {
        return $rules->filter(fn (MeetingAvailabilityRule $rule) => ($rule->meeting_type_id ? (int) $rule->meeting_type_id : null) === $typeId && $rule->format === $format);
    }

    /** Exceptions that apply to the type and format, keyed by local date. */
    private function exceptions(MeetingType $type, MeetingFormat $format, CarbonImmutable $firstDay, CarbonImmutable $lastDay): Collection
    {
        return MeetingAvailabilityException::query()->where('channel_id', $type->channel_id)
            ->whereDate('date', '>=', $firstDay->toDateString())
            ->whereDate('date', '<=', $lastDay->toDateString())
            ->where(fn ($q) => $q->whereNull('meeting_type_id')->orWhere('meeting_type_id', $type->id))
            ->where(fn ($q) => $q->whereNull('format')->orWhere('format', $format->value))
            ->get()
            ->groupBy(fn ($exception) => $exception->date->toDateString());
    }

    /**
     * A day's open windows: weekly hours plus extra hours, minus closures.
     *
     * @param  list<array{0: int, 1: int}>  $weekly
     * @return list<array{0: int, 1: int}>
     */
    private function windows(array $weekly, Collection $exceptions): array
    {
        if ($exceptions->contains(fn ($e) => $e->is_closed && ! $e->start_time)) {
            return [];
        }

        $timed = $exceptions->filter(fn ($e) => $e->start_time && $e->end_time)
            ->map(fn ($e) => [$e->is_closed, self::minutes($e->start_time), self::minutes($e->end_time)]);

        $windows = collect($weekly)
            ->merge($timed->reject(fn ($e) => $e[0])->map(fn ($e) => [$e[1], $e[2]]))
            ->sortBy(0)
            ->reduce(function (array $merged, array $window) {
                $last = array_key_last($merged);
                if ($last !== null && $window[0] <= $merged[$last][1]) {
                    $merged[$last][1] = max($merged[$last][1], $window[1]);
                } else {
                    $merged[] = $window;
                }

                return $merged;
            }, []);

        foreach ($timed->filter(fn ($e) => $e[0]) as [, $closedFrom, $closedTo]) {
            $windows = collect($windows)->flatMap(fn ($w) => array_filter([
                [$w[0], min($w[1], $closedFrom)],
                [max($w[0], $closedTo), $w[1]],
            ], fn ($part) => $part[0] < $part[1]))->values()->all();
        }

        return $windows;
    }

    /**
     * Busy times as [start, end, blocked from, blocked until]: bookings carry
     * their buffers, calendar events do not.
     *
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable, 2: CarbonImmutable, 3: CarbonImmutable}>
     */
    private function busy(int $channelId, CarbonImmutable $from, CarbonImmutable $to, ?MeetingBooking $ignore, bool $fresh): array
    {
        $bookings = MeetingBooking::query()->forChannel($channelId)
            ->whereIn('status', MeetingBooking::BLOCKING_STATUSES)
            ->where('blocked_from', '<', $to)
            ->where('blocked_until', '>', $from)
            ->when($ignore, fn ($q) => $q->whereKeyNot($ignore->getKey()))
            ->get(['starts_at', 'ends_at', 'blocked_from', 'blocked_until'])
            ->toBase()
            ->map(fn (MeetingBooking $b) => array_map(fn ($time) => CarbonImmutable::instance($time)->utc(), [$b->starts_at, $b->ends_at, $b->blocked_from, $b->blocked_until]));

        $events = collect($this->calendar->busyIntervals($channelId, $from, $to, $ignore?->calendar_event_id, $fresh))
            ->map(fn (array $event) => [$event[0]->utc(), $event[1]->utc(), $event[0]->utc(), $event[1]->utc()]);

        return $bookings->merge($events)->all();
    }

    /**
     * A slot clashes when its buffered time overlaps another meeting, or its
     * meeting time falls inside another booking's buffers.
     */
    private function clashes(CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $blockedFrom, CarbonImmutable $blockedUntil, array $busy): bool
    {
        foreach ($busy as [$busyStart, $busyEnd, $busyBlockedFrom, $busyBlockedUntil]) {
            if (($blockedFrom->lt($busyEnd) && $busyStart->lt($blockedUntil)) || ($start->lt($busyBlockedUntil) && $busyBlockedFrom->lt($end))) {
                return true;
            }
        }

        return false;
    }

    /** The instant for a local wall-clock time, or null when it does not exist (DST gap). */
    private function localTime(string $date, int $minute, string $timezone): ?CarbonImmutable
    {
        $wall = sprintf('%s %02d:%02d', $date, intdiv($minute, 60), $minute % 60);
        $time = CarbonImmutable::createFromFormat('!Y-m-d H:i', $wall, $timezone);

        return $time && $time->format('Y-m-d H:i') === $wall ? $time->utc() : null;
    }

    private static function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }
}
