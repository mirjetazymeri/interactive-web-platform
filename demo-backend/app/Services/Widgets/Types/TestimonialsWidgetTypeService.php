<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class TestimonialsWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'testimonials';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['heading']     = trim((string) $request->input('config.heading', ''));
        $config['subheading']  = trim((string) $request->input('config.subheading', ''));
        $config['columns']     = max(1, min(3, (int) $request->input('config.columns', 2)));
        $config['show_rating'] = $request->boolean('config.show_rating', true);
        $config['items']       = $this->processItems($request, $existing['items'] ?? []);

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
                'quote' => trim((string) ($item['quote'] ?? '')),
                'name' => trim((string) ($item['name'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
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
            $item  = (array) $item;
            $quote = trim((string) ($item['quote'] ?? ''));
            $name  = trim((string) ($item['name'] ?? ''));
            $role  = trim((string) ($item['role'] ?? ''));

            if ($quote === '' && $name === '' && $role === '') {
                continue;
            }

            $mediaType = in_array($item['media_type'] ?? '', ['logo', 'image'])
                ? $item['media_type']
                : 'image';

            $logo  = null;
            $image = null;

            if ($mediaType === 'logo') {
                $logoFile = $files[$index]['logo_file'] ?? null;
                if ($logoFile instanceof UploadedFile && $logoFile->isValid()) {
                    $logo = $logoFile->store('widgets/testimonials/logos', 'public');
                } elseif (!empty($item['keep_logo']) && !empty($item['existing_logo'])) {
                    $logo = $item['existing_logo'];
                }
            } else {
                $imageFile = $files[$index]['image_file'] ?? null;
                if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                    $image = $imageFile->store('widgets/testimonials', 'public');
                } elseif (!empty($item['keep_image']) && !empty($item['existing_image'])) {
                    $image = $item['existing_image'];
                }
            }

            $output[] = [
                'quote'      => $quote,
                'name'       => $name,
                'role'       => $role,
                'rating'     => max(1, min(5, (int) ($item['rating'] ?? 5))),
                'media_type' => $mediaType,
                'logo'       => $logo,
                'image'      => $image,
            ];
        }

        return $output;
    }
}
