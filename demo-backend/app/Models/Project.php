<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/** Legacy records retained for the non-destructive widget migration and original media ownership. */
class Project extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const TEXT_FIELDS = ['name', 'project_type', 'category', 'description', 'work_delivered', 'goal'];

    protected $fillable = ['name', 'slug', 'project_type', 'category', 'client_name', 'technologies', 'completion_year', 'description', 'work_delivered', 'goal', 'website_url', 'status', 'position', 'featured'];

    protected $casts = ['technologies' => 'array', 'featured' => 'boolean', 'completion_year' => 'integer'];

    protected static function booted(): void
    {
        static::creating(function (self $project) {
            $project->slug = $project->slug ?: Str::slug($project->name);
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function translations()
    {
        return $this->hasMany(ProjectTranslation::class);
    }

    public function widgets()
    {
        return $this->belongsToMany(Widget::class)->withPivot('position')->withTimestamps();
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function localized(string $field, ?string $locale = null): ?string
    {
        $this->loadMissing('translations');
        $locale ??= app()->getLocale();

        return data_get($this->translations->firstWhere('locale', $locale)?->content, $field)
            ?: data_get($this->translations->firstWhere('locale', config('app.fallback_locale'))?->content, $field)
            ?: $this->getAttribute($field);
    }

}
