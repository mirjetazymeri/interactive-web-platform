<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Locale extends Model
{
    protected $fillable = [
        'code',
        'name',
        'flag',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public static function activeCodes(): array
    {
        return cache()->remember('app:active_locales', 300, function () {
            return static::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->pluck('code')
                ->filter()
                ->values()
                ->all();
        });
    }

    protected static function booted()
    {
        static::saving(function ($locale) {
            if ($locale->is_default) {
                static::where('id', '!=', $locale->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
