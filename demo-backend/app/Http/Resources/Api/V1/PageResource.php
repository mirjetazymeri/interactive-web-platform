<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Seo\SeoService;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();
        $translation = $this->translation($locale);

        return [
            'id'           => $this->id,
            'slug'         => $this->slug,
            'path'         => $this->path, // computed from ancestors
            'title'        => $translation?->title ?: $this->title,
            'content'      => $this->content,
            'layout'       => $this->layout,
            'status'       => $this->status,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'updated_at'   => optional($this->updated_at)->toIso8601String(),
            // title/description plus canonical, sharing, robots and schema.org overrides.
            'meta'         => app(SeoService::class)->pageMeta($this->resource, $locale),
            // Languages with their own content; the website advertises only these.
            'locales'      => app(SeoService::class)->pageLocales($this->resource),
            'parent_id'    => $this->parent_id,
            'breadcrumbs'  => $this->ancestors()->map(function ($a) use ($locale) {
                $t = $a->translation($locale);

                return [
                    'id'    => $a->id,
                    'slug'  => $a->slug,
                    'title' => $t?->title ?: $a->title,
                    'path'  => $a->path,
                ];
            })->values(),
            'children'     => $this->children->map(function ($ch) use ($locale) {
                $t = $ch->translation($locale);

                return [
                    'id'    => $ch->id,
                    'slug'  => $ch->slug,
                    'title' => $t?->title ?: $ch->title,
                    'path'  => $ch->path,
                ];
            })->values(),
            // Widgets are attached in the controller (to keep this resource reusable).
        ];
    }
}
