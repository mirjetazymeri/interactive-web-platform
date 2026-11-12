<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class HtmlWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'html';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['html'] = (string) ($incoming['html'] ?? '');
        $config['css'] = (string) ($incoming['css'] ?? '');
        $config['js'] = (string) ($incoming['js'] ?? '');
        $config['enable_preview'] = $request->boolean('config.enable_preview');

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $config = [];

        foreach (['html', 'css', 'js'] as $key) {
            $value = (string) ($translation[$key] ?? '');
            if ($value !== '') {
                $config[$key] = $value;
            }
        }

        return $config;
    }
}
