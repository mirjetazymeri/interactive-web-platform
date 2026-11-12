<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SubscribeNewsletterRequest;
use App\Http\Requests\Api\V1\UnsubscribeNewsletterRequest;
use App\Services\NewsletterSubscriberService;

class NewsletterController extends Controller
{
    public function subscribe(SubscribeNewsletterRequest $request, NewsletterSubscriberService $service)
    {
        $channel = app('currentChannel');

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not resolved',
            ], 400);
        }

        $subscriber = $service->subscribe(
            $request->validated('email'),
            $request->validated('name'),
            (int) $channel->id,
            'api',
            [
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Newsletter subscription saved.',
            'data' => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
                'unsubscribe_token' => $subscriber->unsubscribe_token,
            ],
            'meta' => [
                'channel_id' => (int) $channel->id,
            ],
        ], 201);
    }

    public function unsubscribe(UnsubscribeNewsletterRequest $request, NewsletterSubscriberService $service)
    {
        $channel = app('currentChannel');

        if (!$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Channel not resolved',
            ], 400);
        }

        $subscriber = $request->filled('token')
            ? $service->unsubscribeByToken($request->validated('token'))
            : $service->unsubscribeByEmail($request->validated('email'), (int) $channel->id);

        if (!$subscriber) {
            return response()->json([
                'success' => false,
                'message' => 'Subscriber not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Newsletter subscription removed.',
            'data' => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
            ],
            'meta' => [
                'channel_id' => (int) $subscriber->channel_id,
            ],
        ]);
    }
}
