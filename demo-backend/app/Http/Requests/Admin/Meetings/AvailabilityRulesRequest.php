<?php

namespace App\Http\Requests\Admin\Meetings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Replaces the weekly hours of one scope (type or global, format or all). */
class AvailabilityRulesRequest extends FormRequest
{
    use ValidatesAvailabilityScope;

    public const MAX_RANGES_PER_DAY = 6;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->scopeRules(),
            'hours' => ['nullable', 'array'],
            'hours.*' => ['array', 'max:'.self::MAX_RANGES_PER_DAY],
            'hours.*.*.start' => ['required', 'date_format:H:i'],
            'hours.*.*.end' => ['required', 'date_format:H:i'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $this->validateScope($validator);

            foreach ((array) $this->input('hours', []) as $weekday => $ranges) {
                if (! in_array((int) $weekday, range(0, 6), true) || (string) (int) $weekday !== (string) $weekday) {
                    $validator->errors()->add('hours', __('meetings.error_weekday'));

                    continue;
                }
                $sorted = collect($ranges)->sortBy('start')->values();
                foreach ($sorted as $i => $range) {
                    if ($range['end'] <= $range['start']) {
                        $validator->errors()->add("hours.$weekday", __('meetings.error_range_order'));
                    } elseif ($i > 0 && $range['start'] < $sorted[$i - 1]['end']) {
                        $validator->errors()->add("hours.$weekday", __('meetings.error_range_overlap'));
                    }
                }
            }
        }];
    }

    /** @return list<array{weekday: int, start_time: string, end_time: string}> */
    public function ranges(): array
    {
        return collect((array) $this->validated('hours', []))
            ->flatMap(fn ($ranges, $weekday) => collect($ranges)->map(fn ($range) => [
                'weekday' => (int) $weekday, 'start_time' => $range['start'], 'end_time' => $range['end'],
            ]))
            ->sortBy(['weekday', 'start_time'])
            ->values()
            ->all();
    }
}
