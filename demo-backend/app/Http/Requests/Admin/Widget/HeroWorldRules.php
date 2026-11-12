<?php

namespace App\Http\Requests\Admin\Widget;

use App\Services\Widgets\Types\HeroWorldWidgetTypeService as HeroWorld;
use Closure;
use Illuminate\Validation\Rule;

class HeroWorldRules
{
    public static function rules(): array
    {
        return [
            'config.headline' => ['required', 'string', 'max:255'],
            'config.subtitle' => ['nullable', 'string', 'max:1000'],
            'config.cta_text' => ['nullable', 'string', 'max:120'],
            'config.cta_link' => ['nullable', 'string', 'max:1000', 'required_with:config.cta_text', self::link(...)],
            'config.seo_title' => ['nullable', 'string', 'max:70'],
            'config.seo_description' => ['nullable', 'string', 'max:320'],
            'config.services' => ['nullable', 'array', 'list', 'max:'.HeroWorld::MAX_SERVICES],
            'config.services.*' => ['array'],
            'config.services.*.id' => ['required', 'string', 'regex:/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', 'distinct:strict'],
            'config.services.*.title' => ['required', 'string', 'max:80'],
            'config.services.*.description' => ['nullable', 'string', 'max:300'],
            'config.services.*.icon' => ['nullable', 'string', Rule::in(HeroWorld::ICONS)],
            'config.services.*.lat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:config.services.*.lng'],
            'config.services.*.lng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:config.services.*.lat'],
            'config.services.*.position' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'config.services.*.active' => ['sometimes', 'boolean'],
            'translations.*.headline' => ['nullable', 'string', 'max:255'],
            'translations.*.subtitle' => ['nullable', 'string', 'max:1000'],
            'translations.*.cta_text' => ['nullable', 'string', 'max:120'],
            'translations.*.seo_title' => ['nullable', 'string', 'max:70'],
            'translations.*.seo_description' => ['nullable', 'string', 'max:320'],
            'translations.*.services' => ['nullable', 'array', 'max:'.HeroWorld::MAX_SERVICES],
            // Translations carry text only: never icons, coordinates, order or status.
            'translations.*.services.*' => ['array:title,description'],
            'translations.*.services.*.title' => ['nullable', 'string', 'max:80'],
            'translations.*.services.*.description' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * Internal routes ("/about", "services", "#contact") or http(s), mailto and
     * tel links. Script-capable schemes, protocol-relative and credentialed
     * URLs are rejected; the frontend applies its own link rules as well.
     */
    public static function link(string $attribute, mixed $value, Closure $fail): void
    {
        $link = trim((string) $value);
        if ($link === '') {
            return;
        }
        $valid = ! preg_match('/[\x00-\x20\\\\]/', $link) && ! str_starts_with($link, '//');
        if ($valid && preg_match('/^[a-z][a-z0-9+.-]*:/i', $link)) {
            $scheme = strtolower(strtok($link, ':'));
            $valid = match ($scheme) {
                'http', 'https' => filter_var($link, FILTER_VALIDATE_URL) !== false
                    && parse_url($link, PHP_URL_USER) === null && parse_url($link, PHP_URL_PASS) === null,
                'mailto', 'tel' => strlen($link) > strlen($scheme) + 1,
                default => false,
            };
        } elseif ($valid) {
            $valid = (bool) preg_match('@^(?:/|#|[A-Za-z0-9])[A-Za-z0-9._~/#?&=%+-]*$@', $link);
        }
        if (! $valid) {
            $fail(__('hero_world.invalid_link'));
        }
    }
}
