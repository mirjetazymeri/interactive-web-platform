<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class FeaturesWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'features';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['heading']    = trim((string) $request->input('config.heading', ''));
        $config['subheading'] = trim((string) $request->input('config.subheading', ''));
        $config['columns']    = max(1, min(4, (int) $request->input('config.columns', 3)));
        $config['items']      = $this->processItems($request, $existing['items'] ?? []);

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $out = [];

        foreach (['heading', 'subheading'] as $key) {
            $value = trim((string) ($translation[$key] ?? ''));
            if ($value !== '') {
                $out[$key] = $value;
            }
        }

        $items = [];
        foreach ((array) ($translation['items'] ?? []) as $idx => $item) {
            $localized = array_filter([
                'title' => trim((string) ($item['title'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
            ], fn ($v) => $v !== '');

            if (!empty($localized)) {
                $items[(int) $idx] = $localized;
            }
        }

        if (!empty($items)) {
            ksort($items);
            $out['items'] = $items;
        }

        return $out;
    }

    private function processItems(Request $request, array $existingItems): array
    {
        $raw   = (array) $request->input('config.items', []);
        $files = $request->file('config.items', []);
        $output = [];

        foreach ($raw as $index => $item) {
            $item        = (array) $item;
            $title       = trim((string) ($item['title'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));

            if ($title === '' && $description === '') {
                continue;
            }

            $mediaType = in_array($item['media_type'] ?? '', ['icon', 'image'])
                ? $item['media_type']
                : 'icon';

            $icon  = null;
            $image = null;

            if ($mediaType === 'icon') {
                $iconFile = $files[$index]['icon_file'] ?? null;
                if ($iconFile instanceof UploadedFile && $iconFile->isValid()) {
                    $icon = $iconFile->store('widgets/features/icons', 'public');
                } elseif (!empty($item['keep_icon']) && !empty($item['existing_icon'])) {
                    $icon = $item['existing_icon'];
                }
            } else {
                $imageFile = $files[$index]['image_file'] ?? null;
                if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                    $image = $imageFile->store('widgets/features', 'public');
                } elseif (!empty($item['keep_image']) && !empty($item['existing_image'])) {
                    $image = $item['existing_image'];
                }
            }

            $output[] = [
                'title'       => $title,
                'description' => $description,
                'media_type'  => $mediaType,
                'icon'        => $icon,
                'image'       => $image,
            ];
        }

        return $output;
    }
}
