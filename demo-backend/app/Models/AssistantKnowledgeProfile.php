<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedText;
use Illuminate\Database\Eloquent\Model;

/**
 * Verified company facts maintained by admins (one per channel). Only a
 * published profile is used by the assistant; a draft is never shown.
 */
class AssistantKnowledgeProfile extends Model
{
    use HasTranslatedText;

    /** Text fields stored per locale in assistant_knowledge_profile_translations.content. */
    public const TEXT_FIELDS = ['introduction', 'description', 'expertise', 'mission', 'additional_information'];

    public const STATUSES = ['draft', 'published'];

    protected $fillable = ['channel_id', 'status', 'introduction', 'description', 'expertise', 'mission', 'capabilities', 'additional_information', 'languages'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['capabilities' => 'array', 'languages' => 'array'];
    }

    public function translations()
    {
        return $this->hasMany(AssistantKnowledgeProfileTranslation::class);
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Capabilities in a language, falling back to the base list. */
    public function translatedCapabilities(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $override = $this->translations->firstWhere('locale', $locale)?->content['capabilities'] ?? null;

        return array_values(array_filter(is_array($override) && $override !== [] ? $override : (array) ($this->capabilities ?? [])));
    }
}
