<?php

namespace App\Enums;

/** Which formats a meeting type offers. */
enum MeetingLocationMode: string
{
    case Online = 'online';
    case InPerson = 'in_person';
    case Both = 'both';

    public function label(): string
    {
        return __('meetings.mode_'.$this->value);
    }

    /** @return list<MeetingFormat> */
    public function formats(): array
    {
        return match ($this) {
            self::Online => [MeetingFormat::Online],
            self::InPerson => [MeetingFormat::InPerson],
            self::Both => [MeetingFormat::Online, MeetingFormat::InPerson],
        };
    }

    public function allows(MeetingFormat $format): bool
    {
        return in_array($format, $this->formats(), true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
