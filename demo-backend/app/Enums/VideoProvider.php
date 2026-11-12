<?php

namespace App\Enums;

/** Video providers for online meetings. Only Teams can create links automatically (via the Microsoft 365 calendar). */
enum VideoProvider: string
{
    case Teams = 'teams';
    case Zoom = 'zoom';
    case Custom = 'custom';

    public function label(): string
    {
        return __('meetings.provider_'.$this->value);
    }

    public function supportsAutoCreate(): bool
    {
        return $this === self::Teams;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
