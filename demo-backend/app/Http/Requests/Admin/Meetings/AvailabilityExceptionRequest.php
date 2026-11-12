<?php

namespace App\Http\Requests\Admin\Meetings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** A one-off change on a date: closed all day, closed for some hours, or extra hours. */
class AvailabilityExceptionRequest extends FormRequest
{
    use ValidatesAvailabilityScope;

    public const KINDS = ['closed_day', 'closed_hours', 'open_hours'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            ...$this->scopeRules(),
            'date' => ['required', 'date_format:Y-m-d'],
            'kind' => ['required', 'in:'.implode(',', self::KINDS)],
            'start_time' => ['exclude_if:kind,closed_day', 'required', 'date_format:H:i'],
            'end_time' => ['exclude_if:kind,closed_day', 'required', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isEmpty()) {
                $this->validateScope($validator);
            }
        }];
    }

    public function payload(): array
    {
        $data = $this->validated();

        return [
            ...$this->scope(),
            'date' => $data['date'],
            'is_closed' => $data['kind'] !== 'open_hours',
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'note' => $data['note'] ?? null,
        ];
    }
}
