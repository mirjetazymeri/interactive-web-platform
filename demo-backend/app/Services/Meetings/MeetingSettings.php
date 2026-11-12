<?php

namespace App\Services\Meetings;

use App\Enums\VideoProvider;
use App\Models\Locale;
use App\Services\SettingService;
use App\Services\Widgets\Types\MeetingBookingWidgetTypeService;

/**
 * Typed access to the private "meetings.*" settings, with defaults from
 * config/meetings.php.
 */
class MeetingSettings
{
    public function __construct(private SettingService $settings) {}

    public function get(string $key, ?int $channelId = null): mixed
    {
        return $this->settings->get('meetings.'.$key, config('meetings.settings.'.$key), $channelId);
    }

    public function bool(string $key, ?int $channelId = null): bool
    {
        return filter_var($this->get($key, $channelId), FILTER_VALIDATE_BOOL);
    }

    public function array(string $key, ?int $channelId = null): array
    {
        return (array) ($this->get($key, $channelId) ?? []);
    }

    public function timezone(?int $channelId = null): string
    {
        return (string) ($this->get('general.timezone', $channelId) ?: config('app.timezone'));
    }

    public function provider(?int $channelId = null): VideoProvider
    {
        return VideoProvider::tryFrom((string) $this->get('online.provider', $channelId)) ?? VideoProvider::Teams;
    }

    /**
     * The online settings as one array, the shape MeetingTypeService checks
     * link availability against.
     *
     * @return array{provider: string, auto_create: bool, static_urls: array, allow_manual_after_booking: bool}
     */
    public function online(?int $channelId = null): array
    {
        return [
            'provider' => $this->provider($channelId)->value,
            'auto_create' => $this->bool('online.auto_create', $channelId),
            'static_urls' => $this->array('online.static_urls', $channelId),
            'allow_manual_after_booking' => $this->bool('online.allow_manual_after_booking', $channelId),
        ];
    }

    /** Save values given with keys relative to "meetings." (e.g. "online.provider"). */
    public function save(array $values, int $channelId): void
    {
        $this->settings->setMany(collect($values)->mapWithKeys(fn ($value, $key) => ['meetings.'.$key => $value])->all(), $channelId);
        // The booking widget shows the timezone.
        MeetingBookingWidgetTypeService::refreshWidgets();
    }

    public function defaultLocale(): string
    {
        return (string) config('app.locale');
    }

    /** Locales that get translation tabs: active CMS locales plus configured ones, minus the default. */
    public function translationLocales(): array
    {
        return collect(Locale::activeCodes())
            ->merge(array_keys(config('locales', [])))
            ->unique()
            ->reject(fn ($code) => $code === $this->defaultLocale())
            ->values()
            ->all();
    }
}
