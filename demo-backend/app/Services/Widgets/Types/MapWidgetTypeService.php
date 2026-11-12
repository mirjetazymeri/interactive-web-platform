<?php

namespace App\Services\Widgets\Types;

use Illuminate\Http\Request;

class MapWidgetTypeService implements WidgetTypeConfigService
{
    public function type(): string
    {
        return 'map';
    }

    public function extendConfig(Request $request, array $config, array $incoming, array $existing): array
    {
        $mode = (string) $request->input('config.mode', 'coordinates');
        $provider = (string) $request->input('config.provider', 'osm');
        $locationMode = (string) $request->input('config.location_mode', 'single');

        $config['mode'] = in_array($mode, ['coordinates', 'embed'], true) ? $mode : 'coordinates';
        $config['provider'] = in_array($provider, ['osm', 'google', 'mapbox'], true) ? $provider : 'osm';
        $config['location_mode'] = in_array($locationMode, ['single', 'multi'], true) ? $locationMode : 'single';
        $config['zoom'] = max(1, min(20, (int) $request->input('config.zoom', 13)));
        $config['marker_title'] = trim((string) $request->input('config.marker_title', ''));
        $config['marker_text'] = trim((string) $request->input('config.marker_text', ''));

        if ($config['mode'] === 'coordinates') {
            if ($config['location_mode'] === 'multi') {
                $config['markers'] = $this->normalizeMarkers((array) $request->input('config.markers', []));

                if (!empty($config['markers'])) {
                    $config['lat'] = $config['markers'][0]['lat'];
                    $config['lng'] = $config['markers'][0]['lng'];
                } else {
                    $config['lat'] = null;
                    $config['lng'] = null;
                }

                $config['marker_title'] = '';
                $config['marker_text'] = '';
            } else {
                $lat = $request->input('config.lat');
                $lng = $request->input('config.lng');

                $config['lat'] = is_numeric($lat) ? (float) $lat : null;
                $config['lng'] = is_numeric($lng) ? (float) $lng : null;
                $config['markers'] = [];
            }

            $config['embed_url'] = null;
        } else {
            $config['embed_url'] = trim((string) $request->input('config.embed_url', ''));
            $config['lat'] = null;
            $config['lng'] = null;
            $config['markers'] = [];
            $config['location_mode'] = 'single';
        }

        return $config;
    }

    public function extractTranslationConfig(array $translation): array
    {
        $out = [];

        $markerTitle = trim((string) ($translation['marker_title'] ?? ''));
        $markerText = trim((string) ($translation['marker_text'] ?? ''));

        if ($markerTitle !== '') {
            $out['marker_title'] = $markerTitle;
        }

        if ($markerText !== '') {
            $out['marker_text'] = $markerText;
        }

        return $out;
    }

    private function normalizeMarkers(array $markers): array
    {
        $output = [];

        foreach ($markers as $marker) {
            $marker = (array) $marker;
            $lat = $marker['lat'] ?? null;
            $lng = $marker['lng'] ?? null;

            if (!is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }

            $output[] = [
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'title' => trim((string) ($marker['title'] ?? '')),
                'text' => trim((string) ($marker['text'] ?? '')),
            ];
        }

        return array_values($output);
    }
}
