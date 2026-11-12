<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    protected $fillable = [
        'page_id',
        'locale',
        'title',
        'content',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'nav_label',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
