<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class VideoWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'video';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['video_url'] = (string) $request->input('config.video_url', '');
        $config['autoplay'] = $request->boolean('config.autoplay');
        $config['muted'] = $request->boolean('config.muted');

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $videoUrl = trim((string) ($translation['video_url'] ?? ''));

        return $videoUrl !== '' ? ['video_url' => $videoUrl] : [];
    }
}
