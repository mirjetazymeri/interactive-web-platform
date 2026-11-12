<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStorefrontConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'visitor_id' => ['nullable', 'string', 'max:191'],
            'session_id' => ['nullable', 'string', 'max:191'],
            'consent_analytics' => ['required', 'boolean'],
            'consent_marketing' => ['nullable', 'boolean'],
            'consent_source' => ['nullable', 'string', 'max:120'],
            'policy_version' => ['nullable', 'string', 'max:80'],
            'preferences' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'visitor_id' => trim((string) $this->input('visitor_id')),
            'session_id' => trim((string) $this->input('session_id')),
            'consent_source' => trim((string) $this->input('consent_source')),
            'policy_version' => trim((string) $this->input('policy_version')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->user('sanctum') && $this->input('visitor_id') === '' && $this->input('session_id') === '') {
                $validator->errors()->add('visitor_id', 'visitor_id or session_id is required for guest consent.');
            }
        });
    }
}
