<?php

namespace App\Http\Requests\Admin\Assistant;

use App\Models\AssistantSynonym;
use App\Models\Locale;
use App\Services\Assistant\AssistantContentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantSynonymRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'terms' => AssistantIntentRequest::lines($this->input('terms')),
            'active' => $this->boolean('active'),
            'locale' => $this->input('locale') ?: null,
            'service_refs' => array_values(array_filter((array) $this->input('service_refs', []), 'is_string')) ?: null,
            'capability' => $this->input('capability') ?: null,
        ]);
    }

    public function rules(): array
    {
        $refs = array_keys(app(AssistantContentService::class)->serviceRefOptions((int) $this->user()->current_channel_id));

        return [
            // Plain synonyms need two terms to mean anything; a service phrase can be a single term ("logo").
            'terms' => ['required', 'array', 'max:30', function (string $attribute, array $value, \Closure $fail) {
                if (count($value) < 2 && ! $this->input('capability') && ! $this->input('service_refs')) {
                    $fail(__('assistant.terms_min'));
                }
            }],
            'terms.*' => ['string', 'max:100'],
            'locale' => ['nullable', 'string', Rule::in([...Locale::activeCodes(), ...array_keys(config('locales', [])), config('app.locale')])],
            'service_refs' => ['nullable', 'array', 'max:10'],
            'service_refs.*' => ['string', Rule::in($refs)],
            'capability' => ['nullable', Rule::in(AssistantSynonym::CAPABILITIES)],
            'active' => ['boolean'],
        ];
    }
}
