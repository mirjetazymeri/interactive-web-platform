<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class CarouselWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'carousel';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['carousel_transition'] = $request->input('config.carousel_transition', 'slide');
        $config['carousel_interval'] = max(1000, (int) $request->input('config.carousel_interval', 5000));
        $config['overlay_opacity'] = max(0, min(100, (int) $request->input('config.overlay_opacity', 40)));
        $config['carousel_loop'] = $request->boolean('config.carousel_loop');
        $config['slides'] = $this->processSlides($request);

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $slides = [];

        foreach ((array) ($translation['slides'] ?? []) as $index => $slide) {
            $localizedSlide = array_filter([
                'alt'      => trim((string) ($slide['alt'] ?? '')),
                'headline' => trim((string) ($slide['headline'] ?? '')),
                'subtitle' => trim((string) ($slide['subtitle'] ?? '')),
                'cta_text' => trim((string) ($slide['cta_text'] ?? '')),
                'cta_link' => trim((string) ($slide['cta_link'] ?? '')),
            ], fn ($value) => $value !== '');

            if (!empty($localizedSlide)) {
                $slides[(int) $index] = $localizedSlide;
            }
        }

        if (!empty($slides)) {
            ksort($slides);
            return ['slides' => $slides];
        }

        return [];
    }

    private function processSlides(Request $request): array
    {
        $disk = 'public';
        $dir = 'widgets';
        $raw = (array) $request->input('config.slides', []);
        $files = $request->file('config.slides', []);
        $output = [];

        foreach ($raw as $index => $slide) {
            $slide = (array) $slide;
            $image = null;

            if (!empty($files[$index]['image_file']) && $files[$index]['image_file'] instanceof \Illuminate\Http\UploadedFile && $files[$index]['image_file']->isValid()) {
                $image = $files[$index]['image_file']->store($dir, $disk);
            } elseif (!empty($slide['keep_image']) && !empty($slide['existing_image'])) {
                $image = $slide['existing_image'];
            }

            $output[] = [
                'image'         => $image,
                'alt'           => trim((string) ($slide['alt'] ?? '')),
                'headline'      => trim((string) ($slide['headline'] ?? '')),
                'subtitle'      => trim((string) ($slide['subtitle'] ?? '')),
                'cta_text'      => trim((string) ($slide['cta_text'] ?? '')),
                'cta_link'      => trim((string) ($slide['cta_link'] ?? '')),
                'cta_color'     => $slide['cta_color'] ?? '#ffffff',
                'cta_bg'        => $slide['cta_bg'] ?? '#0d6efd',
                'text_position' => in_array($slide['text_position'] ?? '', ['left', 'center', 'right'], true)
                    ? $slide['text_position']
                    : 'center',
            ];
        }

        return $output;
    }
}
