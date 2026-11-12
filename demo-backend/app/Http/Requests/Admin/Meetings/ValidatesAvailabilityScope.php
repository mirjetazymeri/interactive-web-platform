<?php

namespace App\Http\Requests\Admin\Meetings;

use App\Enums\MeetingFormat;
use App\Models\MeetingType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** The optional meeting type and format an availability rule or exception applies to. */
trait ValidatesAvailabilityScope
{
    protected function scopeRules(): array
    {
        return [
            'meeting_type_id' => ['nullable', 'integer',
                Rule::exists('meeting_types', 'id')->where('channel_id', $this->channelId())->whereNull('deleted_at')],
            'format' => ['nullable', Rule::in(MeetingFormat::values())],
        ];
    }

    /** A format-specific scope on a type only makes sense for a format the type offers. */
    protected function validateScope(Validator $validator): void
    {
        $format = MeetingFormat::tryFrom((string) $this->input('format'));
        $type = $this->input('meeting_type_id') ? MeetingType::find($this->input('meeting_type_id')) : null;

        if ($type && $format && ! $type->allows($format)) {
            $validator->errors()->add('format', __('meetings.error_scope_format'));
        }
    }

    /** @return array{meeting_type_id: ?int, format: ?string} */
    public function scope(): array
    {
        return [
            'meeting_type_id' => $this->input('meeting_type_id') ? (int) $this->input('meeting_type_id') : null,
            'format' => MeetingFormat::tryFrom((string) $this->input('format'))?->value,
        ];
    }

    protected function channelId(): int
    {
        return (int) $this->user()->current_channel_id;
    }
}
