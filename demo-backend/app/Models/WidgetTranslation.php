<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WidgetTranslation extends Model
{
    protected $fillable = [
        'widget_id',
        'locale',
        'title',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }
}
