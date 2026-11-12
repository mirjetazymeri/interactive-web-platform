<?php

namespace App\Models;

use App\Enums\MeetingFormat;
use Illuminate\Database\Eloquent\Model;

class MeetingAvailabilityException extends Model
{
    protected $fillable = ['channel_id', 'meeting_type_id', 'format', 'date', 'start_time', 'end_time', 'is_closed', 'note'];

    protected function casts(): array
    {
        return [
            'format' => MeetingFormat::class,
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class);
    }
}
