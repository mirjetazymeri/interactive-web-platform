<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantKnowledgeProfileTranslation extends Model
{
    protected $fillable = ['assistant_knowledge_profile_id', 'locale', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }
}
