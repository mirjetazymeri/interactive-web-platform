<?php

namespace App\Models\Concerns;

/** Text fields with per-locale overrides in a translations relation (content JSON), falling back to the base column. */
trait HasTranslatedText
{
    public function translated(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $override = $this->translations->firstWhere('locale', $locale)?->content[$field] ?? null;
        $value = $override !== null && $override !== '' ? $override : $this->getAttribute($field);

        return $value === null || trim((string) $value) === '' ? null : (string) $value;
    }
}
