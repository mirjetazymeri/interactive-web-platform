<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuration reserved for an optional, future AI provider. Inactive: no
 * code path sends requests. The API key is encrypted at rest and hidden from
 * serialization; only a short hint is ever shown.
 */
class AssistantAiSetting extends Model
{
    public const STRATEGIES = ['deterministic', 'hybrid', 'generative'];

    protected $fillable = [
        'channel_id', 'enabled', 'strategy', 'provider', 'model', 'endpoint_url',
        'monthly_request_limit', 'monthly_token_limit', 'monthly_spend_limit', 'spend_currency', 'disable_on_limit',
    ];

    protected $hidden = ['api_key'];

    protected $attributes = ['enabled' => false, 'strategy' => 'deterministic', 'spend_currency' => 'EUR', 'disable_on_limit' => true];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'disable_on_limit' => 'boolean',
            'api_key' => 'encrypted',
            'api_key_updated_at' => 'datetime',
            'monthly_spend_limit' => 'decimal:2',
        ];
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function hasApiKey(): bool
    {
        return filled($this->getRawOriginal('api_key'));
    }

    /** Stores a new key (encrypted) and keeps only its last four characters as a hint. */
    public function replaceApiKey(?string $key): void
    {
        $key = $key === null ? null : trim($key);
        $this->forceFill([
            'api_key' => $key ?: null,
            'api_key_hint' => $key ? mb_substr($key, -4) : null,
            'api_key_updated_at' => $key ? now() : null,
        ]);
    }
}
