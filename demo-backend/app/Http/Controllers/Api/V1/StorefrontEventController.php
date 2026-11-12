<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStorefrontEventRequest;
use App\Models\StorefrontConsent;
use App\Models\StorefrontEvent;
use App\Services\SettingService;

class StorefrontEventController extends Controller
{
    public function store(StoreStorefrontEventRequest $request, SettingService $settings)
    {
        $channel = app('currentChannel');

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not resolved',
            ], 400);
        }

        $channelId = (int) $channel->id;

        $trackingEnabled = (bool) $settings->get('gdpr.analytics_tracking_enabled', false, $channelId);
        $gdprEnabled = (bool) $settings->get('gdpr.enabled', true, $channelId);
        $requireConsent = (bool) $settings->get('gdpr.analytics_require_consent', true, $channelId);
        $consent = $request->has('consent')
            ? (bool) $request->boolean('consent')
            : $this->resolveStoredAnalyticsConsent($request, $channelId);

        if (!$trackingEnabled) {
            return response()->json([
                'success' => true,
                'stored' => false,
                'reason' => 'tracking_disabled',
            ], 202);
        }

        if ($gdprEnabled && $requireConsent && !$consent) {
            return response()->json([
                'success' => true,
                'stored' => false,
                'reason' => 'consent_required',
            ], 202);
        }

        $data = $request->validated();
        $ip = (string) $request->ip();
        $ipHash = $ip !== '' ? hash('sha256', $ip . '|' . (string) config('app.key')) : null;

        $event = StorefrontEvent::create([
            'channel_id' => $channelId,
            'user_id' => $request->user('sanctum')?->id,
            'visitor_id' => $data['visitor_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'event_type' => $data['event_type'],
            'event_name' => $data['event_name'] ?? null,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'url' => $data['url'] ?? null,
            'referrer' => $data['referrer'] ?? null,
            'search_term' => $data['search_term'] ?? null,
            'payload' => $data['payload'] ?? null,
            'consent' => $consent,
            'ip_hash' => $ipHash,
            'user_agent' => (string) $request->userAgent(),
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'stored' => true,
            'data' => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'occurred_at' => optional($event->occurred_at)->toISOString(),
            ],
            'meta' => [
                'channel_id' => $channelId,
            ],
        ], 201);
    }

    protected function resolveStoredAnalyticsConsent(StoreStorefrontEventRequest $request, int $channelId): bool
    {
        $userId = $request->user('sanctum')?->id;
        $visitorId = trim((string) $request->input('visitor_id', ''));
        $sessionId = trim((string) $request->input('session_id', ''));

        $query = StorefrontConsent::query()->where('channel_id', $channelId);

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($visitorId !== '') {
            $query->where('visitor_id', $visitorId);
        } elseif ($sessionId !== '') {
            $query->where('session_id', $sessionId);
        } else {
            return false;
        }

        $consent = $query->latest('id')->first();

        return (bool) ($consent?->consent_analytics ?? false);
    }
}
