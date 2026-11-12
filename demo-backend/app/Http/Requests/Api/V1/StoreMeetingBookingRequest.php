<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MeetingFormat;
use App\Models\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeetingBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $trim = fn (string $key) => $this->filled($key) ? trim((string) $this->input($key)) : null;

        $this->merge([
            'name' => $trim('name'),
            'email' => $trim('email'),
            'phone' => $trim('phone'),
            'company' => $trim('company'),
            'project_details' => $trim('project_details'),
            'locale' => $trim('locale') ?? app()->getLocale(),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'format' => ['required', Rule::in(MeetingFormat::values())],
            'start' => ['required', 'date'],
            'visitor_timezone' => ['required', 'timezone:all'],
            'locale' => ['required', Rule::in(array_unique([...Locale::activeCodes(), ...array_keys(config('locales', [])), config('app.locale')]))],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:191'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:150'],
            'project_details' => ['required', 'string', 'min:10', 'max:5000'],
            'consent' => ['accepted'],
            // Honeypot: real visitors never see or fill this field.
            'website' => ['prohibited'],
        ];
    }

    /** @return array{name: string, email: string, phone: ?string, company: ?string, project_details: string} */
    public function visitor(): array
    {
        return $this->safe()->only(['name', 'email', 'phone', 'company', 'project_details']);
    }
}
