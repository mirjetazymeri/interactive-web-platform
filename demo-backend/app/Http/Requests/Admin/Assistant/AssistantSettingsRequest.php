<?php

namespace App\Http\Requests\Admin\Assistant;

use App\Models\AssistantProfile;
use Illuminate\Foundation\Http\FormRequest;

class AssistantSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
            'show_on_mobile' => $this->boolean('show_on_mobile'),
            'log_unanswered' => $this->boolean('log_unanswered'),
            'response_templates' => $this->templates(),
        ]);
    }

    /** Textareas (one wording per line) to {locale: {set: [wordings]}}; empty sets are left out. */
    private function templates(): ?array
    {
        $out = [];
        foreach ((array) $this->input('response_templates', []) as $locale => $sets) {
            foreach ((array) $sets as $set => $text) {
                $lines = is_string($text) ? AssistantIntentRequest::lines($text) : [];
                if ($lines !== []) {
                    $out[$locale][$set] = $lines;
                }
            }
        }

        return $out ?: null;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'show_on_mobile' => ['boolean'],
            'log_unanswered' => ['boolean'],
            'unanswered_retention_days' => ['required', 'integer', 'min:7', 'max:365'],
            'name' => ['nullable', 'string', 'max:100'],
            'welcome_message' => ['nullable', 'string', 'max:1000'],
            'tooltip' => ['nullable', 'string', 'max:160'],
            'placeholder' => ['nullable', 'string', 'max:160'],
            'fallback_message' => ['nullable', 'string', 'max:1000'],
            'notice' => ['nullable', 'string', 'max:300'],
            'translations' => ['nullable', 'array'],
            'translations.*.name' => ['nullable', 'string', 'max:100'],
            'translations.*.welcome_message' => ['nullable', 'string', 'max:1000'],
            'translations.*.tooltip' => ['nullable', 'string', 'max:160'],
            'translations.*.placeholder' => ['nullable', 'string', 'max:160'],
            'translations.*.fallback_message' => ['nullable', 'string', 'max:1000'],
            'translations.*.notice' => ['nullable', 'string', 'max:300'],
            'response_templates' => ['nullable', 'array'],
            'response_templates.*' => ['array:'.implode(',', array_keys(AssistantProfile::TEMPLATE_SETS))],
            'response_templates.*.*' => ['array', 'max:6'],
            'response_templates.*.*.*' => ['string', 'max:300', function (string $attribute, string $value, \Closure $fail) {
                // Only the placeholders this wording can fill: anything else would reach visitors as typed.
                $set = explode('.', $attribute)[2];
                $unknown = array_diff(preg_match_all('/:([a-z]+)/', $value, $m) ? $m[1] : [], AssistantProfile::TEMPLATE_SETS[$set] ?? []);
                if ($unknown !== []) {
                    $fail(__('assistant.template_placeholders', ['allowed' => implode(', ', array_map(fn ($p) => ':'.$p, AssistantProfile::TEMPLATE_SETS[$set] ?? [])) ?: '—']));
                }
            }],
        ];
    }

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
