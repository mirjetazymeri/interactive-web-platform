<?php

namespace App\Http\Requests\Admin\Assistant;

use App\Models\AssistantKnowledgeProfile;
use App\Services\Assistant\AssistantContentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssistantKnowledgeRequest extends FormRequest
{
    /** Maximum length of each text field. */
    public const LIMITS = ['introduction' => 600, 'description' => 5000, 'expertise' => 3000, 'mission' => 2000, 'additional_information' => 5000];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $translations = (array) $this->input('translations', []);
        foreach ($translations as $locale => $values) {
            $translations[$locale]['capabilities'] = AssistantIntentRequest::lines($values['capabilities'] ?? null);
        }
        $this->merge([
            'capabilities' => AssistantIntentRequest::lines($this->input('capabilities')),
            'languages' => array_values(array_filter((array) $this->input('languages', []))),
            'translations' => $translations,
        ]);
    }

    public function rules(): array
    {
        $content = app(AssistantContentService::class);
        $locales = [$content->defaultLocale(), ...$content->translationLocales()];
        $rules = [
            'status' => ['required', Rule::in(AssistantKnowledgeProfile::STATUSES)],
            'capabilities' => ['array', 'max:20'], 'capabilities.*' => ['string', 'max:200'],
            'languages' => ['array'], 'languages.*' => ['string', Rule::in($locales)],
            'translations' => ['nullable', 'array'],
            'translations.*' => ['array'],
            'translations.*.capabilities' => ['array', 'max:20'], 'translations.*.capabilities.*' => ['string', 'max:200'],
        ];
        foreach (self::LIMITS as $field => $max) {
            $rules[$field] = ['nullable', 'string', 'max:'.$max];
            $rules["translations.*.$field"] = ['nullable', 'string', 'max:'.$max];
        }
        // Publishing needs at least the introduction the assistant would show.
        $rules['introduction'][] = 'required_if:status,published';

        return $rules;
    }

    /** Plain text only. */
    public function payload(): array
    {
        $data = $this->validated();
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = trim(strip_tags($value));
            }
        });

        return $data;
    }
}
