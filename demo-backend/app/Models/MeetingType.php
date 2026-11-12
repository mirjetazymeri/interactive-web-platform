<?php

namespace App\Models;

use App\Enums\MeetingFormat;
use App\Enums\MeetingLocationMode;
use App\Enums\VideoProvider;
use App\Models\Concerns\HasTranslatedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MeetingType extends Model
{
    use HasTranslatedText, SoftDeletes;

    /** Fields stored per locale in meeting_type_translations.content. */
    public const TEXT_FIELDS = ['name', 'description', 'online_instructions', 'in_person_instructions'];

    protected $fillable = [
        'channel_id', 'slug', 'name', 'description', 'online_instructions', 'in_person_instructions',
        'location_mode', 'default_format', 'duration_minutes', 'buffer_before_minutes', 'buffer_after_minutes',
        'online_duration_minutes', 'online_buffer_before_minutes', 'online_buffer_after_minutes',
        'in_person_duration_minutes', 'in_person_buffer_before_minutes', 'in_person_buffer_after_minutes',
        'office_location_id', 'video_provider', 'slot_interval_minutes', 'min_notice_minutes', 'max_advance_days',
        'active', 'position',
    ];

    protected function casts(): array
    {
        return [
            'location_mode' => MeetingLocationMode::class,
            'default_format' => MeetingFormat::class,
            'video_provider' => VideoProvider::class,
            'active' => 'boolean',
        ];
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function translations()
    {
        return $this->hasMany(MeetingTypeTranslation::class);
    }

    public function officeLocation()
    {
        return $this->belongsTo(OfficeLocation::class);
    }

    public function bookings()
    {
        return $this->hasMany(MeetingBooking::class);
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /** @return list<MeetingFormat> */
    public function allowedFormats(): array
    {
        return $this->location_mode->formats();
    }

    public function allows(MeetingFormat $format): bool
    {
        return $this->location_mode->allows($format);
    }

    /**
     * Effective duration and buffers for a format: the format's override when
     * set, otherwise the type's base value.
     *
     * @return array{duration: int, buffer_before: int, buffer_after: int}
     */
    public function settingsFor(MeetingFormat $format): array
    {
        $prefix = $format->value.'_';

        return [
            'duration' => (int) ($this->{$prefix.'duration_minutes'} ?? $this->duration_minutes),
            'buffer_before' => (int) ($this->{$prefix.'buffer_before_minutes'} ?? $this->buffer_before_minutes),
            'buffer_after' => (int) ($this->{$prefix.'buffer_after_minutes'} ?? $this->buffer_after_minutes),
        ];
    }
}
