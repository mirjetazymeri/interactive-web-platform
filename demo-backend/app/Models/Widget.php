<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Widget extends Model
{
    use SoftDeletes;

    protected $fillable = ['type','title','config','status','position','hide_on_home'];

    protected $casts = [
        'config'       => 'array',
        'hide_on_home' => 'boolean',
    ];


    public function pages()
    {
        return $this->belongsToMany(Page::class, 'page_widgets', 'widget_id', 'page_id')
            ->using(PageWidget::class)
            ->withPivot(['id','slot','position','active','visibility','starts_at','ends_at','config','created_at','updated_at'])
            ->withTimestamps()
            ->orderByPivot('slot')
            ->orderByPivot('position');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(WidgetTranslation::class);
    }

    public function translation(?string $locale = null): ?WidgetTranslation
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

    public function localizedTitle(?string $locale = null): ?string
    {
        return $this->translation($locale)?->title ?: $this->title;
    }

    public function localizedConfig(?string $locale = null): array
    {
        $baseConfig = (array) ($this->config ?? []);
        $translatedConfig = (array) ($this->translation($locale)?->config ?? []);

        if ($this->type === 'projects') {
            $this->loadMissing('translations');
            $service = app(\App\Services\Widgets\Types\ProjectsWidgetTypeService::class);
            $fallback = (array) ($this->translations->firstWhere('locale', config('app.fallback_locale'))?->config ?? []);
            return $service->localize($service->localize($baseConfig, $fallback), $translatedConfig);
        }

        if ($this->type === 'service_x') {
            return app(\App\Services\Widgets\Types\ServiceXWidgetTypeService::class)
                ->localize($baseConfig, $translatedConfig);
        }

        if ($this->type === \App\Services\Widgets\Types\HeroWorldWidgetTypeService::TYPE) {
            $this->loadMissing('translations');
            $fallback = (array) ($this->translations->firstWhere('locale', config('app.fallback_locale'))?->config ?? []);

            return app(\App\Services\Widgets\Types\HeroWorldWidgetTypeService::class)
                ->localize($baseConfig, $fallback, $translatedConfig);
        }

        $config = array_replace_recursive($baseConfig, $translatedConfig);
        // A translated label list replaces the base list; recursive replacement
        // would otherwise mix the two lists item by item.
        if ($this->type === 'hero' && isset($translatedConfig['rotating_labels'])) {
            $config['rotating_labels'] = $translatedConfig['rotating_labels'];
        }

        return $config;
    }

    public function scopeActive($q) { return $q->where('status','active'); }

    public function channels()
    {
        return $this->belongsToMany(Channel::class, 'widget_channel');
    }

    public function scopeForChannel($q, $channelId)
    {
        return $q->whereHas('channels', fn($q) => $q->where('channel_id', $channelId));
    }

    public function scopeManageableBy($query, User $user)
    {
        $allowed = $user->channels->pluck('id')->all();

        // A shared widget changes in every channel: require access to all of them.
        return $query->when(empty($allowed), fn ($q) => $q->whereRaw('1 = 0'))
            ->whereDoesntHave('channels', fn ($q) => $q->whereNotIn('channels.id', $allowed));
    }

    public function scopeVisibleInChannel($query, ?int $channelId)
    {
        return $query->where(fn ($q) => $q->whereDoesntHave('channels')
            ->orWhereHas('channels', fn ($channels) => $channels->where('channels.id', $channelId)));
    }
}
