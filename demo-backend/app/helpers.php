<?php

use App\Services\SettingService;

if (!function_exists('setting')) {
    function setting(string $key, $default = null)
    {
        if (!app()->bound(SettingService::class)) {
            return $default;
        }

        return app(SettingService::class)->get($key, $default);
    }
}

if (!function_exists('current_channel')) {
    function current_channel()
    {
        return app()->bound('currentChannel')
            ? app('currentChannel')
            : null;
    }
}
