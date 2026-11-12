<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingBookingEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['meeting_booking_id', 'type', 'payload', 'user_id'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function booking()
    {
        return $this->belongsTo(MeetingBooking::class, 'meeting_booking_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
