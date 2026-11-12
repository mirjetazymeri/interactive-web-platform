<?php

namespace App\Services\Meetings\Calendar;

use App\Services\Meetings\BookingException;

/**
 * The host calendar could not be read and the channel fails closed, so no
 * times are offered or booked rather than risk a clash with unseen events.
 */
class CalendarUnavailableException extends BookingException
{
    public const CALENDAR_UNAVAILABLE = 'calendar_unavailable';

    public function __construct()
    {
        parent::__construct(self::CALENDAR_UNAVAILABLE);
    }
}
