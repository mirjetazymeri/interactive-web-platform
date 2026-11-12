<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug','title','content','layout','status','meta_title','meta_description','published_at',
        'parent_id','position',
        'show_in_header','show_in_footer','nav_label','header_position','footer_position','footer_section','nav_new_tab',
        'canonical_url','og_title','og_description','og_image','robots_index','robots_follow','schema_type',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean',
        'show_in_header',
        'show_in_footer',
        'nav_new_tab',
    ];

    // ---- Relationships ----
    public function parent()   { return $this->belongsTo(Page::class, 'parent_id'); }
    public function children() { return $this->hasMany(Page::class, 'parent_id')->orderBy('position')->orderBy('id'); }

    public function pageWidgets()
    {
        return $this->hasMany(PageWidget::class, 'page_id')
            ->orderBy('slot')
            ->orderBy('position');
    }

    public function widgets()
    {
        return $this->belongsToMany(Widget::class, 'page_widgets', 'page_id', 'widget_id')
            ->using(PageWidget::class)
            ->withPivot(['id','slot','position','active','visibility','starts_at','ends_at','config','created_at','updated_at'])
            ->withTimestamps()
            ->orderBy('page_widgets.slot')
            ->orderBy('page_widgets.position');
    }

    // ---- Scopes ----
    public function scopeRoots($q) { return $q->whereNull('parent_id'); }

    public function scopePublished($q) {
        return $q->where('status','published')
                 ->where(fn($w)=>$w->whereNull('published_at')->orWhere('published_at','<=',now()));
    }

    // ---- Derived helpers ----
    public function getPathAttribute(): string
    {
        $segments = [];
        $node = $this;
        while ($node) {
            $segments[] = $node->slug;
            $node = $node->parent;
        }
        return implode('/', array_reverse($segments));
    }

    public function ancestors()
    {
        $acc = collect();
        $node = $this->parent;
        while ($node) { $acc->push($node); $node = $node->parent; }
        return $acc->reverse()->values();
    }

    public function descendants()
    {
        $out = collect();
        $stack = [$this];
        while ($curr = array_pop($stack)) {
            foreach ($curr->children as $ch) {
                $out->push($ch);
                $stack[] = $ch;
            }
        }
        return $out;
    }

    // ---- Hooks ----
    protected static function booted(): void
    {
        static::creating(function(Page $p){
            if (!$p->slug) $p->slug = Str::slug($p->title ?: (string) Str::uuid());
            if (is_null($p->position)) {
                $p->position = (int) static::where('parent_id',$p->parent_id)->max('position') + 1;
            }
        });

        static::saving(function(Page $p){
            $p->slug = Str::slug($p->slug ?: $p->title ?: (string) Str::uuid());

            // Cycle guard
            if ($p->parent_id && $p->exists) {
                if ((int)$p->parent_id === (int)$p->id) {
                    throw new \RuntimeException('A page cannot be its own parent.');
                }
                $descIds = $p->descendants()->pluck('id')->all();
                if (in_array((int)$p->parent_id, $descIds, true)) {
                    throw new \RuntimeException('Parent cannot be a descendant.');
                }
            }
        });
    }

    // Optional scopes for menus
    public function scopeHeaderMenu($q) {
    return $q->published()->where('show_in_header', true)->orderByRaw('COALESCE(header_position, position), id');
    }
    public function scopeFooterMenu($q) {
        return $q->published()->where('show_in_footer', true)->orderByRaw('COALESCE(footer_position, position), id');
    }

    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'page_channel');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function translation(?string $locale = null): ?PageTranslation
    {
        $locale = $locale ?: app()->getLocale();
        $fallback = config('app.fallback_locale');

        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()
                ->whereIn('locale', array_unique([$locale, $fallback]))
                ->get();

        return $translations->firstWhere('locale', $locale)
            ?: $translations->firstWhere('locale', $fallback);
    }

    public function scopeForChannel($q, $channelId)
    {
        return $q->whereHas('channels', fn($q) => $q->where('channel_id', $channelId));
    }

    public function channelContents()
    {
        return $this->hasMany(PageChannelContent::class);
    }

    public function channelContent($channelId)
    {
        return $this->hasOne(PageChannelContent::class)
            ->where('channel_id', $channelId);
    }
}
