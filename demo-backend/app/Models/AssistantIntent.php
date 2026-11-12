<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A question the assistant can answer, with the wording and hints used to recognise it. */
class AssistantIntent extends Model
{
    use HasTranslatedText, SoftDeletes;

    /** Text fields stored per locale in assistant_intent_translations.content. */
    public const TEXT_FIELDS = ['question', 'answer', 'link_label'];

    /** List fields stored per locale in assistant_intent_translations.content. */
    public const LIST_FIELDS = ['phrasings', 'keywords', 'negative_keywords'];

    /** CMS content an answer can show alongside its text. */
    public const SOURCE_TYPES = ['services', 'service', 'subservice', 'projects', 'project', 'contact', 'company'];

    protected $fillable = [
        'channel_id', 'assistant_category_id', 'question', 'answer', 'phrasings', 'keywords', 'negative_keywords',
        'source_type', 'source_id', 'page_id', 'page_anchor', 'link_label', 'priority',
        'is_suggested', 'suggested_position', 'active', 'position',
    ];

    protected function casts(): array
    {
        return [
            'phrasings' => 'array', 'keywords' => 'array', 'negative_keywords' => 'array',
            'is_suggested' => 'boolean', 'active' => 'boolean', 'priority' => 'integer',
        ];
    }

    public function translations()
    {
        return $this->hasMany(AssistantIntentTranslation::class);
    }

    public function category()
    {
        return $this->belongsTo(AssistantCategory::class, 'assistant_category_id');
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function followups()
    {
        return $this->belongsToMany(self::class, 'assistant_intent_followups', 'assistant_intent_id', 'followup_intent_id')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /** A per-locale list (phrasings, keywords…), falling back to the base list when the locale has none. */
    public function translatedList(string $field, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $override = $this->translations->firstWhere('locale', $locale)?->content[$field] ?? null;
        $list = is_array($override) && $override !== [] ? $override : (array) ($this->getAttribute($field) ?? []);

        return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $list), fn ($item) => $item !== ''));
    }
}
