<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantIntentTranslation extends Model
{
    protected $fillable = ['assistant_intent_id', 'locale', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }
}
