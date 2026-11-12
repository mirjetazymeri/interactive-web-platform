<?php

namespace App\Http\Requests\Admin\Meetings;

use App\Enums\MeetingFormat;
use App\Enums\MeetingLocationMode;
use App\Enums\VideoProvider;
use App\Services\Meetings\MeetingTypeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MeetingTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));

        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : (string) $this->input('name')),
            'active' => $this->boolean('active'),
        ]);
    }

    public function rules(): array
    {
        $channelId = $this->channelId();
        $minutes = ['nullable', 'integer', 'min:0', 'max:240'];
        $duration = ['integer', 'min:5', 'max:480'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:100',
                Rule::unique('meeting_types', 'slug')->where('channel_id', $channelId)->ignore($this->route('type'))],
            'description' => ['nullable', 'string', 'max:5000'],
            'online_instructions' => ['nullable', 'string', 'max:5000'],
            'in_person_instructions' => ['nullable', 'string', 'max:5000'],
            'location_mode' => ['required', Rule::in(MeetingLocationMode::values())],
            'default_format' => ['required', Rule::in(MeetingFormat::values())],
            'duration_minutes' => ['required', ...$duration],
            'buffer_before_minutes' => $minutes,
            'buffer_after_minutes' => $minutes,
            'online_duration_minutes' => ['nullable', ...$duration],
            'online_buffer_before_minutes' => $minutes,
            'online_buffer_after_minutes' => $minutes,
            'in_person_duration_minutes' => ['nullable', ...$duration],
            'in_person_buffer_before_minutes' => $minutes,
            'in_person_buffer_after_minutes' => $minutes,
            'office_location_id' => ['nullable', 'integer',
                Rule::exists('office_locations', 'id')->where('channel_id', $channelId)->whereNull('deleted_at')],
            'video_provider' => ['nullable', Rule::in(VideoProvider::values())],
            'slot_interval_minutes' => ['required', 'integer', 'min:5', 'max:240'],
            'min_notice_minutes' => ['required', 'integer', 'min:0', 'max:43200'],
            'max_advance_days' => ['required', 'integer', 'min:1', 'max:365'],
            'active' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:5000'],
            'translations.*.online_instructions' => ['nullable', 'string', 'max:5000'],
            'translations.*.in_person_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach (app(MeetingTypeService::class)->validationErrors($this->validated(), $this->channelId()) as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        }];
    }

    /** Validated input with empty buffers stored as 0, ready for the model. */
    public function payload(): array
    {
        $data = $this->validated();
        foreach (['buffer_before_minutes', 'buffer_after_minutes'] as $field) {
            $data[$field] = (int) ($data[$field] ?? 0);
        }
        $data['position'] = (int) ($data['position'] ?? 0);

        return $data;
    }

    private function channelId(): int
    {
        return (int) $this->user()->current_channel_id;
    }
}
