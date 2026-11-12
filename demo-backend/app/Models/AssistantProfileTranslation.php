<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantProfileTranslation extends Model
{
    protected $fillable = ['assistant_profile_id', 'locale', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }
}
