<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PageWidget extends Pivot
{
    protected $table = 'page_widgets';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'page_id','widget_id','slot','position','active','visibility','starts_at','ends_at','config'
    ];

    protected $casts = [
        'config'     => 'array',
        'visibility' => 'array',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
        'active'     => 'boolean',
    ];

    public function presentationConfig(): array
    {
        if ($this->widget?->type !== 'service_x') {
            return (array) ($this->config ?? []);
        }

        return ['display_mode' => $this->config['display_mode'] ?? 'slider'];
    }

    // 🔗 back-refs so you can do $pw->widget / $pw->page
    public function page()   { return $this->belongsTo(Page::class, 'page_id'); }
    public function widget() { return $this->belongsTo(Widget::class, 'widget_id'); }

    // Handy scope for runtime filtering
    public function scopeActive($q)
    {
        $now = now();
        return $q->where('active', true)
                 ->where(function ($w) use ($now) {
                     $w->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
                 })
                 ->where(function ($w) use ($now) {
                     $w->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                 });
    }
}


