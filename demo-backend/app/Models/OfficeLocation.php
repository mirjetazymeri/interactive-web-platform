<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfficeLocation extends Model
{
    use HasTranslatedText, SoftDeletes;

    /** Fields stored per locale in office_location_translations.content. */
    public const TEXT_FIELDS = ['name', 'arrival_instructions'];

    protected $fillable = [
        'channel_id', 'name', 'address_line1', 'address_line2', 'postcode', 'city', 'country_id',
        'maps_url', 'arrival_instructions', 'is_default', 'active', 'position',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // One default office per channel.
        static::saved(function (OfficeLocation $office) {
            if ($office->is_default) {
                static::query()->where('channel_id', $office->channel_id)->whereKeyNot($office->getKey())
                    ->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function translations()
    {
        return $this->hasMany(OfficeLocationTranslation::class);
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /** Address fields an in-person confirmation cannot do without. */
    public function hasCompleteAddress(): bool
    {
        return trim((string) $this->address_line1) !== '' && trim((string) $this->city) !== '' && $this->country_id;
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            trim($this->postcode.' '.$this->city),
            $this->country?->name,
        ])->map(fn ($part) => trim((string) $part))->filter()->implode(', ');
    }

    /** The configured map link, or a Google Maps search for the full address. */
    public function mapsLink(): string
    {
        return $this->maps_url ?: 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($this->fullAddress());
    }
}
