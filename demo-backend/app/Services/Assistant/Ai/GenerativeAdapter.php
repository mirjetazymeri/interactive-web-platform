<?php

namespace App\Services\Assistant\Ai;

use App\Models\AssistantAiSetting;

/**
 * The contract a future AI provider adapter must meet before Prog Assistant
 * may use it. No adapter exists yet, so none is registered in
 * config('assistant.ai.adapters') and the assistant stays deterministic.
 *
 * An adapter is only added together with its safeguards and tests: usage
 * limits enforced before each request, provider-side spending caps, prompt
 * grounding in the Company Knowledge Profile and CMS content, timeouts and a
 * deterministic fallback on any failure.
 */
interface GenerativeAdapter
{
    /** The provider key it serves, e.g. "anthropic" (see config('assistant.ai.providers')). */
    public function provider(): string;

    /** Model identifiers the provider currently offers, as reported by the adapter (never hard-coded here). */
    public function models(): array;

    /** Whether it can serve a response strategy: "hybrid" or "generative". */
    public function supports(string $strategy): bool;

    /** Whether the channel's settings are complete enough to call the provider (key, model, limits). */
    public function isConfigured(AssistantAiSetting $settings): bool;
}
