<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assistant\AssistantAiSettingsRequest;
use App\Services\Assistant\Ai\AiProviderRegistry;
use App\Services\Assistant\Ai\AssistantAiSettings;

/** Configuration reserved for a future AI provider. Saving it never contacts any provider. */
class AssistantAiIntegrationController extends Controller
{
    public function __construct(private AssistantAiSettings $ai, private AiProviderRegistry $providers) {}

    public function edit()
    {
        $channelId = $this->channelId();
        $settings = $this->ai->for($channelId);

        return view('admin.assistant.ai-integration', [
            'settings' => $settings,
            'providers' => $this->providers->providers(),
            'models' => $this->providers->models($settings->provider),
            'available' => $this->providers->availableStrategies($settings->provider),
            'canEnable' => $this->ai->canEnable($settings->provider),
            'effective' => $this->ai->effectiveStrategy($channelId),
            'currencies' => AssistantAiSettingsRequest::CURRENCIES,
        ]);
    }

    public function update(AssistantAiSettingsRequest $request)
    {
        $this->ai->save($this->channelId(), $request->payload());

        return redirect()->route('admin.assistant.ai.edit')->with('success', __('assistant.saved'));
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
