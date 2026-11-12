<?php

namespace App\Services\Meetings;

use RuntimeException;

/**
 * A booking request or change that cannot go ahead. The reason is a stable
 * code for API clients; the message is a translated sentence for people.
 */
class BookingException extends RuntimeException
{
    public const SLOT_TAKEN = 'slot_taken';
    public const TYPE_UNAVAILABLE = 'type_unavailable';
    public const FORMAT_UNAVAILABLE = 'format_unavailable';
    public const OFFICE_UNAVAILABLE = 'office_unavailable';
    public const NOT_CHANGEABLE = 'not_changeable';

    public function __construct(public readonly string $reason)
    {
        parent::__construct(__('meetings.booking_error_'.$reason));
    }
}
