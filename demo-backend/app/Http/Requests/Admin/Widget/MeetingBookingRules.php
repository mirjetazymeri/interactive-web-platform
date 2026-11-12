<?php

namespace App\Http\Requests\Admin\Widget;

use App\Services\Widgets\Types\MeetingBookingWidgetTypeService as MeetingBooking;
use Illuminate\Validation\Rule;

class MeetingBookingRules
{
    public static function rules(int $channelId): array
    {
        $rules = [
            'config.meeting_types' => ['required', 'array', 'min:1', 'max:50'],
            'config.meeting_types.*' => ['integer', 'distinct',
                Rule::exists('meeting_types', 'id')->where('channel_id', $channelId)->whereNull('deleted_at')],
        ];

        foreach (MeetingBooking::TEXT_FIELDS as $field) {
            $max = in_array($field, MeetingBooking::LONG_FIELDS, true) ? 1000 : 255;
            $rules["config.$field"] = ['nullable', 'string', "max:$max"];
            $rules["translations.*.$field"] = ['nullable', 'string', "max:$max"];
        }

        return $rules;
    }
}
