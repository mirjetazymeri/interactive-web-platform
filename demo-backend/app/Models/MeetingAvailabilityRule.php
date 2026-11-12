<?php

namespace App\Models;

use App\Enums\MeetingFormat;
use Illuminate\Database\Eloquent\Model;

class MeetingAvailabilityRule extends Model
{
    protected $fillable = ['channel_id', 'meeting_type_id', 'format', 'weekday', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return [
            'format' => MeetingFormat::class,
            'weekday' => 'integer',
        ];
    }

    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class);
    }
}
