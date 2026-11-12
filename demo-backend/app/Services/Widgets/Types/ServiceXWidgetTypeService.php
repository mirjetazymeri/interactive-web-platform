<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class ServiceXWidgetTypeService implements WidgetTypeConfigService
{
    public const TEXT_FIELDS = ['name', 'title', 'subtitle', 'image_alt'];

    public function type(): string
    {
        return 'service_x';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $config['section_index'] = trim((string) ($incoming['section_index'] ?? ''));
        $config['services'] = [];
        $previous = collect($existing['services'] ?? [])->keyBy('id');

        foreach (($incoming['services'] ?? []) as $index => $service) {
            $item = ['id' => $service['id']];
            foreach (self::TEXT_FIELDS as $field) {
                $item[$field] = trim((string) ($service[$field] ?? ''));
            }
            $item['position'] = (int) ($service['position'] ?? $index + 1);
            $item['active'] = (bool) ($service['active'] ?? true);
            // Retain media by identity, never by list index or a client-supplied path.
            $item['image'] = $previous->get($item['id'])['image'] ?? null;
            if ($request->boolean("config.services.$index.remove_image")) {
                $item['image'] = null;
            } elseif ($request->hasFile("config.services.$index.image_file")) {
                $item['image'] = $request->file("config.services.$index.image_file")
                    ->store('widgets/service-x', 'public');
                if ($item['image'] === false) {
                    throw new \RuntimeException('Unable to store ServiceX image.');
                }
            }
            $item['subservices'] = [];
            foreach (($service['subservices'] ?? []) as $subIndex => $subservice) {
                $item['subservices'][] = [
                    'id' => $subservice['id'],
                    'title' => trim($subservice['title']),
                    'description' => trim((string) ($subservice['description'] ?? '')),
                    'position' => (int) ($subservice['position'] ?? $subIndex + 1),
                    'active' => (bool) ($subservice['active'] ?? true),
                ];
            }
            $config['services'][] = $item;
        }

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $out = $this->textOnly($translation, ['section_index']);
        foreach (($translation['services'] ?? []) as $id => $service) {
            $text = $this->textOnly($service, self::TEXT_FIELDS);
            foreach (($service['subservices'] ?? []) as $subId => $subservice) {
                $subText = $this->textOnly($subservice, ['title', 'description']);
                if ($subText !== []) {
                    $text['subservices'][$subId] = $subText;
                }
            }
            if ($text !== []) {
                $out['services'][$id] = $text;
            }
        }

        return $out;
    }

    public function localize(array $base, array $translation): array
    {
        $text = $this->extractTranslationConfig($translation);
        $config = array_replace($base, $this->textOnly($translation, ['section_label', 'section_index']));
        $config['services'] = [];
        foreach (($base['services'] ?? []) as $service) {
            $override = $text['services'][$service['id']] ?? [];
            $item = array_replace($service, $this->textOnly($override, self::TEXT_FIELDS));
            $item['subservices'] = [];
            foreach (($service['subservices'] ?? []) as $subservice) {
                $item['subservices'][] = array_replace($subservice,
                    $override['subservices'][$subservice['id']] ?? []);
            }
            $config['services'][] = $item;
        }

        return $config;
    }

    private function textOnly(array $input, array $fields): array
    {
        $output = [];
        foreach ($fields as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if ($value !== '') {
                $output[$field] = $value;
            }
        }

        return $output;
    }
}
