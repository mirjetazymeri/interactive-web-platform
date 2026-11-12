<?php

namespace App\Enums;

/** How a meeting takes place. */
enum MeetingFormat: string
{
    case Online = 'online';
    case InPerson = 'in_person';

    public function label(): string
    {
        return __('meetings.format_'.$this->value);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
