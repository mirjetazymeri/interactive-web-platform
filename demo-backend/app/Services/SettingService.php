<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    // -----------------------------
    // 🔥 CHANNEL RESOLUTION
    // -----------------------------
    protected function resolveChannelId($channelId = null): int
    {
        if ($channelId) {
            return (int) $channelId;
        }

        if (app()->bound('currentChannel')) {
            $channel = app('currentChannel');

            if ($channel?->id) {
                return (int) $channel->id;
            }
        }

        $fallbackChannelId = Channel::query()->orderBy('id')->value('id');
        if ($fallbackChannelId) {
            return (int) $fallbackChannelId;
        }

        return 0;
    }

    protected function cacheKey($channelId): string
    {
        return "app_settings_channel_{$channelId}";
    }

    protected function apiCacheKey($channelId): string
    {
        return "api.settings.v1.channel_{$channelId}";
    }

    // -----------------------------
    // 📦 GET ALL SETTINGS
    // -----------------------------
    public function all($channelId = null): array
    {
        $channelId = $this->resolveChannelId($channelId);

        return Cache::rememberForever(
            $this->cacheKey($channelId),
            function () use ($channelId) {
                return Setting::where('channel_id', $channelId)
                    ->pluck('value', 'key')
                    ->toArray();
            }
        );
    }

    // -----------------------------
    // 🔍 GET SINGLE SETTING
    // -----------------------------
    public function get(string $key, $default = null, $channelId = null)
    {
        $value = $this->all($channelId)[$key] ?? null;

        if ($value === null) {
            $value = $this->getDefaultFromConfig($key) ?? $default;
        }

        // Password fields saved from the settings page are encrypted at rest;
        // values stored before that are plain text and are returned unchanged.
        if (is_string($value) && (data_get(config('settings'), $key)['type'] ?? null) === 'password') {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException) {
                return $value;
            }
        }

        if (is_string($value) && $this->isJson($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    // -----------------------------
    // 💾 SAVE SETTINGS
    // -----------------------------
    public function setMany(array $data, $channelId = null): void
    {
        $channelId = $this->resolveChannelId($channelId);

        foreach ($data as $key => $value) {

            if (is_array($value)) {
                $value = json_encode($value);
            }

            Setting::updateOrCreate(
                [
                    'key'        => $key,
                    'channel_id' => $channelId,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        $this->clearCache($channelId);
    }

    // -----------------------------
    // 🧹 CLEAR CACHE
    // -----------------------------
    public function clearCache($channelId): void
    {
        Cache::forget($this->cacheKey($channelId));
        Cache::forget($this->apiCacheKey($channelId));
    }

    // -----------------------------
    // ⚙ DEFAULTS
    // FIX Bug 3: The old implementation only checked one level deep,
    // missing all nested keys (e.g. header.main.sticky).
    // data_get() handles dot notation across any depth natively.
    // -----------------------------
    protected function getDefaultFromConfig(string $key)
    {
        // $key is dot-notated e.g. "header.main.sticky"
        // data_get traverses the nested config array automatically
        $field = data_get(config('settings'), $key);

        return $field['default'] ?? null;
    }

    protected function isJson($string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
