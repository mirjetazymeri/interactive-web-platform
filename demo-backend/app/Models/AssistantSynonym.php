<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Terms a visitor may use interchangeably, e.g. "ecommerce", "webshop", "online store".
 * A group can also name CMS services ("chatbot" → AI Assistants & Chatbots) and say how
 * far the company commits to it (a capability).
 */
class AssistantSynonym extends Model
{
    /** confirmed: offered; related: the team confirms the scope; unsupported: not offered. */
    public const CAPABILITIES = ['confirmed', 'related', 'unsupported'];

    protected $fillable = ['channel_id', 'locale', 'terms', 'service_refs', 'capability', 'active'];

    protected function casts(): array
    {
        return ['terms' => 'array', 'service_refs' => 'array', 'active' => 'boolean'];
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /** Linked to services or carrying a capability status: a service phrase rather than plain synonyms. */
    public function isServicePhrase(): bool
    {
        return $this->capability !== null || ! empty($this->service_refs);
    }
}
