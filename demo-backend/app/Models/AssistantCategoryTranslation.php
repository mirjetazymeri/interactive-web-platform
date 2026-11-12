<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantCategoryTranslation extends Model
{
    protected $fillable = ['assistant_category_id', 'locale', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }
}
