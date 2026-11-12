<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingCalendarConnection extends Model
{
    protected $fillable = [
        'channel_id', 'provider', 'account_email', 'calendar_id', 'access_token', 'refresh_token',
        'token_expires_at', 'scopes', 'status', 'last_error',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected' && $this->refresh_token && $this->calendar_id;
    }
}
