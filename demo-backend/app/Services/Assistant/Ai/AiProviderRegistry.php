<?php

namespace App\Services\Assistant\Ai;

/**
 * Providers an admin may pre-select, and the adapters actually installed.
 * Providers are only names until an adapter for them is registered in
 * config('assistant.ai.adapters'); today that list is empty.
 */
class AiProviderRegistry
{
    /** @return array<string, string> provider key => label */
    public function providers(): array
    {
        return (array) config('assistant.ai.providers', []);
    }

    public function adapter(?string $provider): ?GenerativeAdapter
    {
        $class = $provider ? (config('assistant.ai.adapters', [])[$provider] ?? null) : null;
        if (! $class || ! class_exists($class)) {
            return null;
        }
        $adapter = app($class);

        return $adapter instanceof GenerativeAdapter && $adapter->provider() === $provider ? $adapter : null;
    }

    /** Model identifiers offered by the provider's adapter; empty while no adapter is installed. */
    public function models(?string $provider): array
    {
        return $this->adapter($provider)?->models() ?? [];
    }

    /** Strategies that can be selected for a provider: deterministic always, others only with a supporting adapter. */
    public function availableStrategies(?string $provider): array
    {
        $adapter = $this->adapter($provider);

        return array_values(array_filter(['deterministic', 'hybrid', 'generative'], fn ($strategy) => $strategy === 'deterministic' || $adapter?->supports($strategy)));
    }
}
