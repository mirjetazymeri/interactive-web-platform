<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedText;
use Illuminate\Database\Eloquent\Model;

class AssistantProfile extends Model
{
    use HasTranslatedText;

    /** Fields stored per locale in assistant_profile_translations.content. */
    public const TEXT_FIELDS = ['name', 'welcome_message', 'tooltip', 'placeholder', 'fallback_message', 'notice'];

    /**
     * Composed answers whose wording an admin may replace, per language (several variants each).
     * Placeholders: :company, :name (the service), :parent (its main service), :services, :wanted, :names.
     */
    public const TEMPLATE_SETS = [
        'capability_subservice' => ['company', 'name', 'parent'],
        'capability_service' => ['company', 'name'],
        'capability_closing' => [],
        'several' => ['company', 'services'],
        'noted_instead' => ['wanted', 'names'],
    ];

    protected $fillable = [
        'channel_id', 'enabled', 'show_on_mobile', 'name', 'welcome_message', 'tooltip', 'placeholder',
        'fallback_message', 'notice', 'log_unanswered', 'unanswered_retention_days', 'response_templates',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'show_on_mobile' => 'boolean', 'log_unanswered' => 'boolean', 'response_templates' => 'array'];
    }

    public function translations()
    {
        return $this->hasMany(AssistantProfileTranslation::class);
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }
}
