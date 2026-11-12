<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Str;

class NewsletterSubscriberService
{
    public function subscribe(
        string $email,
        ?string $name = null,
        ?int $channelId = null,
        string $source = 'api',
        array $meta = []
    ): NewsletterSubscriber {
        $channelId = $this->resolveChannelId($channelId);
        $email = $this->normalizeEmail($email);

        $subscriber = NewsletterSubscriber::firstOrNew([
            'channel_id' => $channelId,
            'email' => $email,
        ]);

        $existingMeta = is_array($subscriber->meta) ? $subscriber->meta : [];

        $subscriber->fill([
            'name' => $name ?: $subscriber->name,
            'status' => 'subscribed',
            'source' => $source ?: $subscriber->source,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
            'meta' => array_merge($existingMeta, $meta),
        ]);

        if (!$subscriber->unsubscribe_token) {
            $subscriber->unsubscribe_token = (string) Str::uuid();
        }

        $subscriber->save();

        return $subscriber->fresh();
    }

    public function unsubscribeByEmail(string $email, ?int $channelId = null): ?NewsletterSubscriber
    {
        $channelId = $this->resolveChannelId($channelId);
        $email = $this->normalizeEmail($email);

        $subscriber = NewsletterSubscriber::query()
            ->where('channel_id', $channelId)
            ->where('email', $email)
            ->first();

        if (!$subscriber) {
            return null;
        }

        return $this->markUnsubscribed($subscriber);
    }

    public function unsubscribeByToken(string $token): ?NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token', trim($token))
            ->first();

        if (!$subscriber) {
            return null;
        }

        return $this->markUnsubscribed($subscriber);
    }

    protected function markUnsubscribed(NewsletterSubscriber $subscriber): NewsletterSubscriber
    {
        $subscriber->forceFill([
            'status' => 'unsubscribed',
            'unsubscribed_at' => $subscriber->unsubscribed_at ?: now(),
        ])->save();

        return $subscriber->fresh();
    }

    protected function resolveChannelId(?int $channelId = null): int
    {
        if ($channelId) {
            return $channelId;
        }

        if (app()->bound('currentChannel') && app('currentChannel')?->id) {
            return (int) app('currentChannel')->id;
        }

        return (int) Channel::query()->orderBy('id')->value('id');
    }

    protected function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
