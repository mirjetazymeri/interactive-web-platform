<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use App\Http\Resources\Api\V1\SettingResource;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index(SettingService $settings)
    {
        $channel = app('currentChannel');

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not resolved'
            ], 400);
        }

        $channelId = (int) $channel->id;

        $cacheKey = "api.settings.v1.channel_{$channelId}";

        $data = Cache::rememberForever($cacheKey, function () use ($settings, $channelId) {
            return new SettingResource(
                $settings->all($channelId)
            );
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'channel_id' => $channelId
            ]
        ]);
    }
}
