<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedText;
use Illuminate\Database\Eloquent\Model;

class AssistantCategory extends Model
{
    use HasTranslatedText;

    public const TEXT_FIELDS = ['name'];

    protected $fillable = ['channel_id', 'name', 'active', 'position'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function translations()
    {
        return $this->hasMany(AssistantCategoryTranslation::class);
    }

    public function intents()
    {
        return $this->hasMany(AssistantIntent::class);
    }

    public function scopeForChannel($query, ?int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }
}
