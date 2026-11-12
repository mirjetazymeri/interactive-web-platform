<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStorefrontConsentRequest;
use App\Models\StorefrontConsent;
use App\Services\SettingService;
use Illuminate\Http\Request;

class StorefrontConsentController extends Controller
{
    public function show(Request $request)
    {
        $channel = app('currentChannel');

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not resolved',
            ], 400);
        }

        $consent = $this->resolveConsent($request, (int) $channel->id);

        return response()->json([
            'success' => true,
            'data' => [
                'consent_analytics' => (bool) ($consent?->consent_analytics ?? false),
                'consent_marketing' => (bool) ($consent?->consent_marketing ?? false),
                'policy_version' => $consent?->policy_version,
                'consented_at' => optional($consent?->consented_at)->toISOString(),
                'withdrawn_at' => optional($consent?->withdrawn_at)->toISOString(),
            ],
            'meta' => [
                'channel_id' => (int) $channel->id,
                'has_record' => (bool) $consent,
            ],
        ]);
    }

    public function store(StoreStorefrontConsentRequest $request, SettingService $settings)
    {
        $channel = app('currentChannel');

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not resolved',
            ], 400);
        }

        $channelId = (int) $channel->id;
        $data = $request->validated();
        $policyVersion = $data['policy_version'] ?: (string) $settings->get('gdpr.consent_version', 'v1', $channelId);
        $ip = (string) $request->ip();
        $ipHash = $ip !== '' ? hash('sha256', $ip . '|' . (string) config('app.key')) : null;

        $identity = $this->identityQuery($request, $channelId);

        $consent = StorefrontConsent::query()->firstOrNew($identity);
        $consent->fill([
            'channel_id' => $channelId,
            'user_id' => $request->user('sanctum')?->id,
            'visitor_id' => $data['visitor_id'] ?: null,
            'session_id' => $data['session_id'] ?: null,
            'consent_analytics' => (bool) $data['consent_analytics'],
            'consent_marketing' => (bool) ($data['consent_marketing'] ?? false),
            'consent_source' => $data['consent_source'] ?: 'cookie_banner',
            'policy_version' => $policyVersion,
            'preferences' => $data['preferences'] ?? null,
            'ip_hash' => $ipHash,
            'user_agent' => (string) $request->userAgent(),
            'consented_at' => now(),
            'withdrawn_at' => (bool) $data['consent_analytics'] ? null : now(),
        ]);
        $consent->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $consent->id,
                'consent_analytics' => (bool) $consent->consent_analytics,
                'consent_marketing' => (bool) $consent->consent_marketing,
                'policy_version' => $consent->policy_version,
                'consented_at' => optional($consent->consented_at)->toISOString(),
                'withdrawn_at' => optional($consent->withdrawn_at)->toISOString(),
            ],
            'meta' => [
                'channel_id' => $channelId,
            ],
        ], 201);
    }

    public function revoke(Request $request)
    {
        $channel = app('currentChannel');

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not resolved',
            ], 400);
        }

        $consent = $this->resolveConsent($request, (int) $channel->id);

        if (!$consent) {
            return response()->json([
                'success' => false,
                'message' => 'Consent record not found.',
            ], 404);
        }

        $consent->forceFill([
            'consent_analytics' => false,
            'consent_marketing' => false,
            'withdrawn_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Consent revoked.',
            'data' => [
                'id' => $consent->id,
                'withdrawn_at' => optional($consent->withdrawn_at)->toISOString(),
            ],
        ]);
    }

    protected function resolveConsent(Request $request, int $channelId): ?StorefrontConsent
    {
        return StorefrontConsent::query()->where($this->identityQuery($request, $channelId))->latest('id')->first();
    }

    protected function identityQuery(Request $request, int $channelId): array
    {
        $userId = $request->user('sanctum')?->id;
        $visitorId = trim((string) $request->input('visitor_id', $request->query('visitor_id', '')));
        $sessionId = trim((string) $request->input('session_id', $request->query('session_id', '')));

        if ($userId) {
            return ['channel_id' => $channelId, 'user_id' => $userId];
        }

        if ($visitorId !== '') {
            return ['channel_id' => $channelId, 'visitor_id' => $visitorId];
        }

        if ($sessionId !== '') {
            return ['channel_id' => $channelId, 'session_id' => $sessionId];
        }

        return ['channel_id' => $channelId, 'id' => 0];
    }
}
