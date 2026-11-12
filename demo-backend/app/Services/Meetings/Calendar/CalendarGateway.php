<?php

namespace App\Services\Meetings\Calendar;

use Carbon\CarbonImmutable;

/** The host calendar the availability engine checks besides stored bookings. */
interface CalendarGateway
{
    /**
     * Busy intervals on the channel's host calendar between $from and $to (UTC),
     * leaving out the event with $excludeEventId (a booking being moved).
     * $fresh skips any cache, for the final check before a booking is saved.
     *
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     *
     * @throws CalendarUnavailableException when the calendar can't be read and the channel fails closed
     */
    public function busyIntervals(int $channelId, CarbonImmutable $from, CarbonImmutable $to, ?string $excludeEventId = null, bool $fresh = false): array;
}
