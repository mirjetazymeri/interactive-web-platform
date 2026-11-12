<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class SeparatorWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'separator';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['style']            = (string) $request->input('config.style', 'line');
        $config['thickness']        = max(1, min(20, (int) $request->input('config.thickness', 1)));
        $config['color']            = (string) $request->input('config.color', '#dee2e6');
        $config['width']            = (string) $request->input('config.width', '100%');
        $config['spacing_top']      = max(0, (int) $request->input('config.spacing_top', 24));
        $config['spacing_bottom']   = max(0, (int) $request->input('config.spacing_bottom', 24));
        $config['alignment']        = (string) $request->input('config.alignment', 'center');
        $config['label']            = trim((string) $request->input('config.label', ''));
        $config['label_bg']         = (string) $request->input('config.label_bg', '#ffffff');
        $config['label_color']      = (string) $request->input('config.label_color', '#6c757d');

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $config = [];

        $label = trim((string) ($translation['label'] ?? ''));
        if ($label !== '') {
            $config['label'] = $label;
        }

        return $config;
    }
}
