<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStorefrontEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_type' => ['required', 'string', 'in:click,search,view,navigation,add_to_cart,custom'],
            'event_name' => ['nullable', 'string', 'max:120'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'entity_id' => ['nullable', 'string', 'max:120'],
            'url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'search_term' => ['nullable', 'string', 'max:255'],
            'visitor_id' => ['nullable', 'string', 'max:191'],
            'session_id' => ['nullable', 'string', 'max:191'],
            'payload' => ['nullable', 'array'],
            'consent' => ['nullable', 'boolean'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_type' => strtolower(trim((string) $this->input('event_type'))),
            'event_name' => trim((string) $this->input('event_name')),
            'entity_type' => trim((string) $this->input('entity_type')),
            'entity_id' => trim((string) $this->input('entity_id')),
            'url' => trim((string) $this->input('url')),
            'referrer' => trim((string) $this->input('referrer')),
            'search_term' => trim((string) $this->input('search_term')),
            'visitor_id' => trim((string) $this->input('visitor_id')),
            'session_id' => trim((string) $this->input('session_id')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('event_type') === 'search' && $this->input('search_term') === '') {
                $validator->errors()->add('search_term', 'search_term is required when event_type is search.');
            }
        });
    }
}
