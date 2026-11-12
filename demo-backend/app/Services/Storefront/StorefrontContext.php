<?php

namespace App\Services\Storefront;

use App\Models\Channel;

final class StorefrontContext
{
    public function __construct(
        public readonly array $settings,
        public readonly array $cssVariables,
        public readonly ?Channel $channel = null,
    ) {}

    public function get(string $path, mixed $default = null): mixed
    {
        return data_get($this->settings, $path, $default);
    }

    public function bool(string $path, bool $default = false): bool
    {
        $value = $this->get($path, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function int(string $path, int $default = 0): int
    {
        return (int) $this->get($path, $default);
    }

    public function assetUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $normalized = ltrim(trim($path), '/');

        if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
            return $normalized;
        }

        if (str_starts_with($normalized, 'storage/')) {
            return asset($normalized);
        }

        return asset('storage/' . $normalized);
    }

    /** @return list<string> */
    public function footerSections(): array
    {
        $raw = (string) $this->get('footer.sections', '');

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw) ?: [])));
    }

    /** @return array<string, string|null> */
    public function socialLinks(): array
    {
        $links = [];
        foreach (['facebook', 'instagram', 'twitter', 'youtube', 'tiktok', 'linkedin', 'pinterest'] as $network) {
            $url = trim((string) $this->get("social.{$network}", ''));
            if ($url !== '') {
                $links[$network] = $url;
            }
        }

        return $links;
    }

    public function cssVariableString(): string
    {
        return collect($this->cssVariables)
            ->map(fn ($value, $name) => "{$name}: {$value}")
            ->implode('; ');
    }
}
