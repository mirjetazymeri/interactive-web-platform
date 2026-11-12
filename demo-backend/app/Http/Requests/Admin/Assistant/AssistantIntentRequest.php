<?php

namespace App\Http\Requests\Admin\Assistant;

use App\Models\AssistantIntent;
use App\Services\Assistant\AssistantContentService;
use App\Services\Assistant\Resolvers\PagesResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssistantIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = ['active' => $this->boolean('active'), 'is_suggested' => $this->boolean('is_suggested')];
        foreach (AssistantIntent::LIST_FIELDS as $field) {
            $merge[$field] = self::lines($this->input($field));
        }
        $translations = (array) $this->input('translations', []);
        foreach ($translations as $locale => $values) {
            foreach (AssistantIntent::LIST_FIELDS as $field) {
                $translations[$locale][$field] = self::lines($values[$field] ?? null);
            }
        }
        $merge['translations'] = $translations;
        $this->merge($merge);
    }

    public function rules(): array
    {
        $channelId = $this->channelId();
        $list = ['array', 'max:50'];
        $item = ['string', 'max:255'];

        return [
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3000'],
            'assistant_category_id' => ['nullable', 'integer', Rule::exists('assistant_categories', 'id')->where('channel_id', $channelId)],
            'phrasings' => $list, 'phrasings.*' => $item,
            'keywords' => $list, 'keywords.*' => $item,
            'negative_keywords' => $list, 'negative_keywords.*' => $item,
            'source' => ['nullable', 'string', 'max:220'],
            'page_id' => ['nullable', 'integer', Rule::exists('page_channel', 'page_id')->where('channel_id', $channelId)],
            'page_anchor' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z][A-Za-z0-9_-]*$/'],
            'link_label' => ['nullable', 'string', 'max:80'],
            'followups' => ['nullable', 'array', 'max:6'],
            'followups.*' => ['integer', Rule::exists('assistant_intents', 'id')->where('channel_id', $channelId)->whereNull('deleted_at')],
            'priority' => ['nullable', 'integer', Rule::in([-1, 0, 1])],
            'is_suggested' => ['boolean'],
            'active' => ['boolean'],
            'from_unanswered' => ['nullable', 'integer'],
            'translations' => ['nullable', 'array'],
            'translations.*.question' => ['nullable', 'string', 'max:255'],
            'translations.*.answer' => ['nullable', 'string', 'max:3000'],
            'translations.*.link_label' => ['nullable', 'string', 'max:80'],
            'translations.*.phrasings' => $list, 'translations.*.phrasings.*' => $item,
            'translations.*.keywords' => $list, 'translations.*.keywords.*' => $item,
            'translations.*.negative_keywords' => $list, 'translations.*.negative_keywords.*' => $item,
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $source = $this->input('source');
            if ($source && ! $this->sourceExists($source)) {
                $validator->errors()->add('source', __('assistant.missing_source'));
            }
            if ($this->input('page_anchor') && ! PagesResolver::validAnchor((string) $this->input('page_anchor'))) {
                $validator->errors()->add('page_anchor', __('validation.regex', ['attribute' => 'page anchor']));
            }
        }];
    }

    /** Validated input ready for the model: plain text only, the source split into type and id. */
    public function payload(): array
    {
        $data = $this->validated();
        [$data['source_type'], $data['source_id']] = AssistantContentService::splitSource($data['source'] ?? null);
        unset($data['source']);
        foreach (['question', 'answer', 'link_label'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = self::plain($data[$field]);
            }
        }
        foreach ($data['translations'] ?? [] as $locale => $values) {
            foreach (['question', 'answer', 'link_label'] as $field) {
                if (isset($values[$field])) {
                    $data['translations'][$locale][$field] = self::plain($values[$field]);
                }
            }
        }
        $data['priority'] = (int) ($data['priority'] ?? 0);
        $data['page_anchor'] = $data['page_anchor'] ?? null;

        return $data;
    }

    /** Textarea input as a list: one entry per line, trimmed, without duplicates or empty lines. */
    public static function lines(mixed $value): array
    {
        $lines = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string) $value);

        return array_values(array_unique(array_filter(array_map(fn ($line) => trim(strip_tags((string) $line)), $lines ?: []), fn ($line) => $line !== '')));
    }

    public static function plain(string $value): string
    {
        return trim(strip_tags($value));
    }

    private function sourceExists(string $source): bool
    {
        if (in_array($source, ['contact', 'services', 'projects', 'company'], true)) {
            return true;
        }
        // An unchanged source stays valid even if its content was unpublished meanwhile.
        $intent = $this->route('question') ? AssistantIntent::query()->forChannel($this->channelId())->find($this->route('question')) : null;
        if ($intent && AssistantContentService::joinSource($intent->source_type, $intent->source_id) === $source) {
            return true;
        }
        $options = app(AssistantContentService::class)->sourceOptions($this->channelId());

        return isset($options['services'][$source]) || isset($options['projects'][$source]);
    }

    private function channelId(): int
    {
        return (int) $this->user()->current_channel_id;
    }
}
