<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class FaqWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'faq';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['heading'] = trim((string) $request->input('config.heading', ''));
        $config['subheading'] = trim((string) $request->input('config.subheading', ''));
        $config['layout_mode'] = (string) $request->input('config.layout_mode', 'accordion');
        $config['allow_multiple_open'] = $request->boolean('config.allow_multiple_open');
        $config['items'] = $this->processItems($request, $existing['items'] ?? []);

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
                'question' => trim((string) ($item['question'] ?? '')),
                'answer' => trim((string) ($item['answer'] ?? '')),
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
        $raw = (array) $request->input('config.items', []);
        $files = $request->file('config.items', []);
        $output = [];

        foreach ($raw as $index => $item) {
            $item = (array) $item;
            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            if ($question === '' && $answer === '') {
                continue;
            }

            $mediaType = in_array($item['media_type'] ?? '', ['icon', 'image'])
                ? $item['media_type']
                : 'icon';

            $icon = null;
            $image = null;

            if ($mediaType === 'icon') {
                $iconFile = $files[$index]['icon_file'] ?? null;
                if ($iconFile instanceof UploadedFile && $iconFile->isValid()) {
                    $icon = $iconFile->store('widgets/faq/icons', 'public');
                } elseif (!empty($item['keep_icon']) && !empty($item['existing_icon'])) {
                    $icon = $item['existing_icon'];
                }
            } else {
                $imageFile = $files[$index]['image_file'] ?? null;
                if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
                    $image = $imageFile->store('widgets/faq', 'public');
                } elseif (!empty($item['keep_image']) && !empty($item['existing_image'])) {
                    $image = $item['existing_image'];
                }
            }

            $output[] = [
                'question' => $question,
                'answer' => $answer,
                'media_type' => $mediaType,
                'icon' => $icon,
                'image' => $image,
            ];
        }

        return $output;
    }
}
