<?php

namespace App\Http\Requests\Admin\Meetings;

use App\Enums\VideoProvider;
use App\Services\Meetings\MeetingTypeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MeetingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $recipients = collect(preg_split('/[\s,;]+/', (string) $this->input('admin_recipients')))
            ->map(fn ($email) => trim($email))->filter()->unique()->values()->all();

        $this->merge([
            'admin_recipients' => $recipients,
            'require_approval' => $this->boolean('require_approval'),
            'fail_closed_when_calendar_unreachable' => $this->boolean('fail_closed_when_calendar_unreachable'),
            'auto_create' => $this->boolean('auto_create'),
            'allow_manual_after_booking' => $this->boolean('allow_manual_after_booking'),
        ]);
    }

    public function rules(): array
    {
        return [
            'timezone' => ['required', 'timezone:all'],
            'require_approval' => ['boolean'],
            'admin_recipients' => ['array', 'max:10'],
            'admin_recipients.*' => ['email:rfc', 'max:191'],
            'organizer_name' => ['nullable', 'string', 'max:150'],
            'fail_closed_when_calendar_unreachable' => ['boolean'],
            'provider' => ['required', Rule::in(VideoProvider::values())],
            'auto_create' => ['boolean'],
            'static_urls' => ['nullable', 'array'],
            'static_urls.*' => ['nullable', 'url:https', 'max:2048'],
            'allow_manual_after_booking' => ['boolean'],
            'joining_instructions' => ['nullable', 'array'],
            'joining_instructions.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $types = app(MeetingTypeService::class)->typesWithoutOnlineLink($this->online(), (int) $this->user()->current_channel_id);
            if ($types->isNotEmpty()) {
                $validator->errors()->add('provider', __('meetings.error_settings_online_link', ['types' => $types->pluck('name')->implode(', ')]));
            }
        }];
    }

    /** The online settings in the shape MeetingTypeService checks. */
    public function online(): array
    {
        return [
            'provider' => (string) $this->input('provider'),
            'auto_create' => (bool) $this->input('auto_create'),
            'static_urls' => $this->staticUrls(),
            'allow_manual_after_booking' => (bool) $this->input('allow_manual_after_booking'),
        ];
    }

    /** Values to store, keyed relative to "meetings.". */
    public function settings(): array
    {
        $data = $this->validated();

        return [
            'general.timezone' => $data['timezone'],
            'general.require_approval' => $data['require_approval'] ? '1' : '0',
            'general.admin_recipients' => $data['admin_recipients'],
            'general.organizer_name' => trim((string) ($data['organizer_name'] ?? '')),
            'general.fail_closed_when_calendar_unreachable' => $data['fail_closed_when_calendar_unreachable'] ? '1' : '0',
            'online.provider' => $data['provider'],
            'online.auto_create' => $data['auto_create'] ? '1' : '0',
            'online.static_urls' => $this->staticUrls(),
            'online.allow_manual_after_booking' => $data['allow_manual_after_booking'] ? '1' : '0',
            'online.joining_instructions' => collect($data['joining_instructions'] ?? [])
                ->map(fn ($text) => trim((string) $text))->filter()->all(),
        ];
    }

    private function staticUrls(): array
    {
        return collect((array) $this->input('static_urls', []))
            ->only(VideoProvider::values())
            ->map(fn ($url) => trim((string) $url))
            ->filter()
            ->all();
    }
}
