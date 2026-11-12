<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StorefrontConsent;
use App\Models\StorefrontEvent;
use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    public function export(Request $request)
    {
        $user = $request->user('sanctum');

        $events = StorefrontEvent::query()
            ->where('user_id', $user->id)
            ->select(['id', 'event_type', 'event_name', 'entity_type', 'entity_id', 'url', 'referrer', 'search_term', 'payload', 'occurred_at', 'created_at'])
            ->orderByDesc('occurred_at')
            ->limit(5000)
            ->get();

        $consents = StorefrontConsent::query()
            ->where('user_id', $user->id)
            ->select(['id', 'consent_analytics', 'consent_marketing', 'policy_version', 'consent_source', 'consented_at', 'withdrawn_at', 'created_at'])
            ->orderByDesc('consented_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $events,
                'consents' => $consents,
            ],
            'meta' => [
                'events_count' => $events->count(),
                'consents_count' => $consents->count(),
            ],
        ]);
    }

    public function destroyTracking(Request $request)
    {
        $user = $request->user('sanctum');

        $eventsDeleted = StorefrontEvent::query()
            ->where('user_id', $user->id)
            ->delete();

        $consentsDeleted = StorefrontConsent::query()
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tracking data deleted for authenticated user.',
            'data' => [
                'events_deleted' => $eventsDeleted,
                'consents_deleted' => $consentsDeleted,
            ],
        ]);
    }
}
