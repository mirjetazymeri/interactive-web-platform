<?php

namespace App\Models;

use App\Enums\MeetingFormat;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class MeetingBooking extends Model
{
    use HasUlids;

    /** Statuses that occupy the host calendar. */
    public const BLOCKING_STATUSES = ['confirmed', 'pending_approval'];

    protected $fillable = [
        'channel_id', 'meeting_type_id', 'format', 'office_location_id',
        'starts_at', 'ends_at', 'blocked_from', 'blocked_until', 'status', 'visitor_timezone', 'locale',
        'name', 'email', 'phone', 'company', 'project_details', 'answers',
        'video_provider', 'video_url', 'video_link_source', 'video_link_status',
        'calendar_id', 'calendar_event_id', 'calendar_sync_status', 'calendar_sync_error',
        'location_snapshot', 'manage_token_hash', 'confirmed_at', 'cancelled_at', 'cancel_reason', 'cancelled_by',
    ];

    protected $hidden = ['manage_token_hash'];

    protected function casts(): array
    {
        return [
            'format' => MeetingFormat::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'blocked_from' => 'datetime',
            'blocked_until' => 'datetime',
            'answers' => 'array',
            'location_snapshot' => 'array',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /** The ULID lives in "reference"; the numeric id stays the primary key. */
    public function uniqueIds(): array
    {
        return ['reference'];
    }

    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class)->withTrashed();
    }

    public function officeLocation()
    {
        return $this->belongsTo(OfficeLocation::class)->withTrashed();
    }

    public function events()
    {
        return $this->hasMany(MeetingBookingEvent::class);
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /** Bookings still ahead that hold a calendar slot. */
    public function scopeUpcomingBlocking($query)
    {
        return $query->whereIn('status', self::BLOCKING_STATUSES)->where('ends_at', '>', now());
    }
}
