<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class ImageGalleryWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'image_gallery';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['columns']       = max(1, min(6, (int) $request->input('config.columns', 3)));
        $config['gap']           = max(0, min(60, (int) $request->input('config.gap', 12)));
        $config['aspect_ratio']  = (string) $request->input('config.aspect_ratio', 'square');
        $config['hover_effect']  = (string) $request->input('config.hover_effect', 'zoom');
        $config['lightbox']      = $request->boolean('config.lightbox', true);
        $config['items']         = $this->processItems($request, $existing['items'] ?? []);

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $items = [];

        foreach ((array) ($translation['items'] ?? []) as $index => $item) {
            $localized = array_filter([
                'alt'     => trim((string) ($item['alt'] ?? '')),
                'caption' => trim((string) ($item['caption'] ?? '')),
            ], fn ($v) => $v !== '');

            if (!empty($localized)) {
                $items[(int) $index] = $localized;
            }
        }

        if (!empty($items)) {
            ksort($items);
            return ['items' => $items];
        }

        return [];
    }

    private function processItems(Request $request, array $existingItems): array
    {
        $raw   = (array) $request->input('config.items', []);
        $files = $request->file('config.items', []);
        $output = [];

        foreach ($raw as $index => $item) {
            $item  = (array) $item;
            $image = null;

            $file = $files[$index]['image_file'] ?? null;
            if ($file instanceof UploadedFile && $file->isValid()) {
                $image = $file->store('widgets/gallery', 'public');
            } elseif (!empty($item['keep_image']) && !empty($item['existing_image'])) {
                $image = $item['existing_image'];
            }

            $output[] = [
                'image'   => $image,
                'alt'     => trim((string) ($item['alt'] ?? '')),
                'caption' => trim((string) ($item['caption'] ?? '')),
                'link'    => trim((string) ($item['link'] ?? '')),
            ];
        }

        return $output;
    }
}
