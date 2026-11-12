<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class PricingWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'pricing';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['heading'] = trim((string) $request->input('config.heading', ''));
        $config['subheading'] = trim((string) $request->input('config.subheading', ''));
        $config['currency'] = trim((string) $request->input('config.currency', '$'));
        $config['billing_note'] = trim((string) $request->input('config.billing_note', 'Billed monthly'));
        $config['columns'] = max(1, min(4, (int) $request->input('config.columns', 3)));
        $config['highlight_popular'] = $request->boolean('config.highlight_popular', true);
        $config['items'] = $this->normalizeItems((array) $request->input('config.items', []));

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $out = [];

        foreach (['heading', 'subheading', 'billing_note'] as $key) {
            $value = trim((string) ($translation[$key] ?? ''));
            if ($value !== '') {
                $out[$key] = $value;
            }
        }

        $items = [];
        foreach ((array) ($translation['items'] ?? []) as $idx => $item) {
            $localized = array_filter([
                'name' => trim((string) ($item['name'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
                'badge' => trim((string) ($item['badge'] ?? '')),
                'period' => trim((string) ($item['period'] ?? '')),
                'features' => trim((string) ($item['features'] ?? '')),
                'cta_text' => trim((string) ($item['cta_text'] ?? '')),
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

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $item = (array) $item;

            $name = trim((string) ($item['name'] ?? ''));
            $description = trim((string) ($item['description'] ?? ''));
            $price = trim((string) ($item['price'] ?? ''));
            $period = trim((string) ($item['period'] ?? '/month'));
            $badge = trim((string) ($item['badge'] ?? ''));
            $features = trim((string) ($item['features'] ?? ''));
            $ctaText = trim((string) ($item['cta_text'] ?? 'Get started'));
            $ctaLink = trim((string) ($item['cta_link'] ?? ''));
            $isPopular = filter_var($item['is_popular'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($name === '' && $price === '' && $description === '') {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'period' => $period,
                'badge' => $badge,
                'features' => $features,
                'cta_text' => $ctaText,
                'cta_link' => $ctaLink,
                'is_popular' => $isPopular,
            ];
        }

        return $normalized;
    }
}
