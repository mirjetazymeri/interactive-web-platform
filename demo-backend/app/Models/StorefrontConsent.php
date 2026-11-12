<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontConsent extends Model
{
    protected $fillable = [
        'channel_id',
        'user_id',
        'visitor_id',
        'session_id',
        'consent_analytics',
        'consent_marketing',
        'consent_source',
        'policy_version',
        'preferences',
        'ip_hash',
        'user_agent',
        'consented_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'consent_analytics' => 'boolean',
        'consent_marketing' => 'boolean',
        'preferences' => 'array',
        'consented_at' => 'datetime',
        'withdrawn_at' => 'datetime',
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
