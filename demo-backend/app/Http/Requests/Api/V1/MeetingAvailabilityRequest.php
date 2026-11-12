<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MeetingFormat;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MeetingAvailabilityRequest extends FormRequest
{
    /** Longest range one request may ask for. */
    public const MAX_DAYS = 31;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'format' => ['required', Rule::in(MeetingFormat::values())],
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after:from'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isEmpty() && $this->from()->diffInDays($this->to()) > self::MAX_DAYS) {
                $validator->errors()->add('to', __('meetings.error_range_too_long', ['days' => self::MAX_DAYS]));
            }
        }];
    }

    public function from(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->input('from'))->utc();
    }

    public function to(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->input('to'))->utc();
    }
}
