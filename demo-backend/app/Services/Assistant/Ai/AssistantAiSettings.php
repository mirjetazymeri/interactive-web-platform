<?php

namespace App\Services\Assistant\Ai;

use App\Models\AssistantAiSetting;

/**
 * Reading and saving the future AI configuration, and deciding which
 * strategy is actually in effect. Fails closed: whatever is stored, the
 * effective strategy is "deterministic" unless an installed adapter supports
 * the chosen mode and is fully configured. Today no adapter exists.
 */
class AssistantAiSettings
{
    public function __construct(private AiProviderRegistry $providers) {}

    /** The channel's settings, or unsaved secure defaults (disabled, deterministic, no key). */
    public function for(int $channelId): AssistantAiSetting
    {
        return AssistantAiSetting::query()->forChannel($channelId)->first() ?? new AssistantAiSetting(['channel_id' => $channelId]);
    }

    public function effectiveStrategy(int $channelId): string
    {
        $settings = AssistantAiSetting::query()->forChannel($channelId)->first();
        if (! $settings || ! $settings->enabled || $settings->strategy === 'deterministic') {
            return 'deterministic';
        }
        $adapter = $this->providers->adapter($settings->provider);

        return $adapter && $adapter->supports($settings->strategy) && $adapter->isConfigured($settings) ? $settings->strategy : 'deterministic';
    }

    /** Whether the integration could be switched on at all: needs an installed adapter for the provider. */
    public function canEnable(?string $provider): bool
    {
        return $this->providers->adapter($provider) !== null;
    }

    /**
     * @param  array{provider?: ?string, model?: ?string, endpoint_url?: ?string, strategy?: string, enabled?: bool,
     *   monthly_request_limit?: ?int, monthly_token_limit?: ?int, monthly_spend_limit?: ?string, spend_currency?: string, disable_on_limit?: bool,
     *   api_key?: ?string, remove_api_key?: bool}  $data  validated input
     */
    public function save(int $channelId, array $data): AssistantAiSetting
    {
        $settings = AssistantAiSetting::query()->firstOrNew(['channel_id' => $channelId]);
        $settings->fill(collect($data)->except(['api_key', 'remove_api_key'])->all());
        if (! empty($data['remove_api_key'])) {
            $settings->replaceApiKey(null);
        } elseif (filled($data['api_key'] ?? null)) {
            $settings->replaceApiKey($data['api_key']);
        }
        $settings->save();

        return $settings;
    }
}
