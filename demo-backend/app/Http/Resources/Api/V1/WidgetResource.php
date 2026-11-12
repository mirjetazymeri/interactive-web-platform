<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Cms\WidgetRenderer;
use Illuminate\Http\Resources\Json\JsonResource;

class WidgetResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();
        $config = app(WidgetRenderer::class)->resolve($this->resource, ['locale' => $locale]);

        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'widget_key' => WidgetRenderer::widgetKey($this->resource),
            'title'      => $this->localizedTitle($locale),
            'config'     => $config,
        ];
    }
}
