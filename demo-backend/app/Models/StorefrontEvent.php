<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontEvent extends Model
{
    protected $fillable = [
        'channel_id',
        'user_id',
        'visitor_id',
        'session_id',
        'event_type',
        'event_name',
        'entity_type',
        'entity_id',
        'url',
        'referrer',
        'search_term',
        'payload',
        'consent',
        'ip_hash',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'consent' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
