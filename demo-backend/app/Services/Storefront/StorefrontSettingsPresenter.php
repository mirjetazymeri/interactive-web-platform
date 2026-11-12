<?php

namespace App\Services\Storefront;

use App\Http\Resources\Api\V1\SettingResource;
use App\Services\SettingService;
use Illuminate\Http\Request;

class StorefrontSettingsPresenter
{
    private const SHADOW_MAP = [
        'none'   => 'none',
        'light'  => '0 6px 16px rgba(15, 23, 42, .08)',
        'medium' => '0 10px 28px rgba(15, 23, 42, .14)',
        'heavy'  => '0 18px 40px rgba(15, 23, 42, .2)',
    ];

    public function __construct(private readonly SettingService $settings) {}

    public function present(?int $channelId = null): StorefrontContext
    {
        $flat = $this->settings->all($channelId);
        $nested = (new SettingResource($flat))->toArray(Request::create('/'));

        return new StorefrontContext(
            settings: $nested,
            cssVariables: $this->buildCssVariables($flat),
            channel: app()->bound('currentChannel') ? app('currentChannel') : null,
        );
    }

    /**
     * @param  array<string, mixed>  $flat
     * @return array<string, string>
     */
    private function buildCssVariables(array $flat): array
    {
        $int = fn (string $key, int $default, int $min = 0) => max(
            $min,
            (int) ($flat[$key] ?? $this->settings->get($key, $default))
        );

        $float = fn (string $key, float $default, float $min = 0) => max(
            $min,
            (float) ($flat[$key] ?? $this->settings->get($key, $default))
        );

        $headerShadowKey = (string) ($flat['layout.header_shadow'] ?? $this->settings->get('layout.header_shadow', 'light'));
        $cardShadowKey   = (string) ($flat['layout.card_shadow'] ?? $this->settings->get('layout.card_shadow', 'medium'));

        $spacing = max(2, $int('layout.spacing_unit', 8, 2));

        $primary   = (string) ($flat['theme.primary_color'] ?? $this->settings->get('theme.primary_color', '#4f46e5'));
        $secondary = (string) ($flat['theme.secondary_color'] ?? $this->settings->get('theme.secondary_color', '#64748b'));
        $accent    = (string) ($flat['theme.accent_color'] ?? $this->settings->get('theme.accent_color', '#f59e0b'));

        return [
            '--sf-max-width'          => $int('layout.max_width', 1200, 320) . 'px',
            '--sf-container-padding'  => $int('layout.container_padding', 24) . 'px',
            '--sf-gutter'             => $int('layout.gutter_width', 24) . 'px',
            '--sf-font-size'          => $int('layout.base_font_size', 16, 12) . 'px',
            '--sf-line-height'        => (string) $float('layout.line_height_normal', 1.5, 1),
            '--sf-line-height-tight'  => (string) $float('layout.line_height_tight', 1.2, 1),
            '--sf-radius-sm'          => $int('layout.border_radius_sm', 4) . 'px',
            '--sf-radius-md'          => $int('layout.border_radius_md', 8) . 'px',
            '--sf-radius-lg'          => $int('layout.border_radius_lg', 12) . 'px',
            '--sf-radius-xl'          => $int('layout.border_radius_xl', 16) . 'px',
            '--sf-card-radius'        => $int('layout.card_border_radius', 12) . 'px',
            '--sf-card-padding'       => $int('layout.card_padding', 24, 8) . 'px',
            '--sf-card-shadow'        => self::SHADOW_MAP[$cardShadowKey] ?? self::SHADOW_MAP['medium'],
            '--sf-btn-radius'         => $int('layout.button_border_radius', 6) . 'px',
            '--sf-btn-px'             => $int('layout.button_padding_x', 20, 8) . 'px',
            '--sf-btn-py'             => $int('layout.button_padding_y', 12, 4) . 'px',
            '--sf-spacing'            => $spacing . 'px',
            '--sf-spacing-sm'         => ($spacing * 2) . 'px',
            '--sf-spacing-md'         => ($spacing * 3) . 'px',
            '--sf-spacing-lg'         => ($spacing * 4) . 'px',
            '--sf-header-height'      => $int('layout.header_height', 80, 48) . 'px',
            '--sf-footer-min-height'  => $int('layout.footer_height', 100, 48) . 'px',
            '--sf-header-shadow'      => self::SHADOW_MAP[$headerShadowKey] ?? self::SHADOW_MAP['light'],
            '--sf-color-primary'      => $primary,
            '--sf-color-secondary'    => $secondary,
            '--sf-color-accent'       => $accent,
            '--sf-bp-sm'              => $int('layout.breakpoint_sm', 576) . 'px',
            '--sf-bp-md'              => $int('layout.breakpoint_md', 768) . 'px',
            '--sf-bp-lg'              => $int('layout.breakpoint_lg', 992) . 'px',
            '--sf-bp-xl'              => $int('layout.breakpoint_xl', 1200) . 'px',
        ];
    }
}
