<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A scrubbed, aggregated visitor question the assistant could not answer. */
class AssistantUnansweredQuestion extends Model
{
    public const STATUSES = ['new', 'reviewed', 'ignored', 'resolved'];

    protected $fillable = [
        'channel_id', 'locale', 'hash', 'question', 'occurrences', 'first_seen_at', 'last_seen_at', 'status', 'resolved_intent_id',
    ];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'occurrences' => 'integer'];
    }

    public function resolvedIntent()
    {
        return $this->belongsTo(AssistantIntent::class, 'resolved_intent_id');
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }
}
