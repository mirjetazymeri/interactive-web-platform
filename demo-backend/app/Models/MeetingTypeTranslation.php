<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingTypeTranslation extends Model
{
    protected $fillable = ['meeting_type_id', 'locale', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function meetingType()
    {
        return $this->belongsTo(MeetingType::class);
    }
}
