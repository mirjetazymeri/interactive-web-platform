<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SetLocaleFromRequest
{
    public function handle(Request $request, Closure $next)
    {
        // Configure your supported locales in config/app.php:
        // 'supported_locales' => ['en', 'de', 'fr', 'sr', 'en-GB', ...],
        $supported = collect(config('app.supported_locales', [config('app.locale', 'en')]))
            ->map(fn ($l) => $this->normalize($l))
            ->filter()
            ->values()
            ->all();

        $fallback = $this->normalize(config('app.locale', config('app.fallback_locale', 'en')));

        // 1) Explicit overrides take precedence
        $explicit = $request->query('locale')
            ?? $request->header('X-Locale');

        $candidate = $this->pickValid($explicit, $supported)
            // 2) Parse Accept-Language (handles q-values, ignores "*")
            ?? $this->pickFromAcceptLanguage($request->header('Accept-Language'), $supported)
            // 3) Fallback
            ?? $fallback;

        app()->setLocale($candidate);

        // optional: stash for downstream consumers
        $request->attributes->set('api_locale', $candidate);

        return $next($request);
    }

    private function pickValid(?string $value, array $supported): ?string
    {
        if (!$value) return null;
        $norm = $this->normalize($value);
        if (!$norm) return null;

        // exact match first
        if (in_array($norm, $supported, true)) {
            return $norm;
        }

        // try base (e.g., "en-us" -> "en")
        $base = Str::before($norm, '-');
        if ($base && in_array($base, $supported, true)) {
            return $base;
        }

        return null;
    }

    private function pickFromAcceptLanguage(?string $header, array $supported): ?string
    {
        if (!$header) return null;

        // Example: "en-US,en;q=0.9,fr;q=0.8,*;q=0.7"
        $items = collect(explode(',', $header))
            ->map(fn ($part) => trim($part))
            ->filter()
            ->map(function (string $part) {
                // split on ;q=
                $segments = array_map('trim', explode(';', $part));
                $tag = $segments[0] ?? '';
                $q = 1.0;

                if (isset($segments[1]) && str_starts_with(Str::lower($segments[1]), 'q=')) {
                    $qVal = (float) substr($segments[1], 2);
                    if ($qVal > 0 && $qVal <= 1) $q = $qVal;
                }

                return [
                    'tag' => $this->normalize($tag),
                    'q'   => $q,
                ];
            })
            ->filter(fn ($x) => $x['tag'] && $x['tag'] !== '*')
            ->sortByDesc('q')
            ->values();

        foreach ($items as $item) {
            if ($match = $this->pickValid($item['tag'], $supported)) {
                return $match;
            }
        }

        return null;
    }

    private function normalize(?string $locale): ?string
    {
        if (!$locale) return null;

        $l = trim($locale);
        if ($l === '' || $l === '*') return null;

        // Replace underscore with hyphen, lower-case the language, upper-case the region (convention)
        $l = str_replace('_', '-', $l);
        $parts = explode('-', $l, 2);
        $lang = Str::lower($parts[0]);

        if ($lang === '') return null;

        if (isset($parts[1]) && $parts[1] !== '') {
            $region = Str::upper($parts[1]);
            return "{$lang}-{$region}";
        }

        return $lang;
    }
}
