<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assistant\AssistantSettingsRequest;
use App\Models\AssistantIntent;
use App\Services\Assistant\AssistantContentService;

class AssistantSettingsController extends Controller
{
    public function __construct(private AssistantContentService $content) {}

    public function edit()
    {
        $channelId = $this->channelId();

        return view('admin.assistant.settings', [
            'profile' => $this->content->profile($channelId),
            'translationLocales' => $this->content->translationLocales(),
            'defaultLocale' => $this->content->defaultLocale(),
            'suggested' => AssistantIntent::query()->forChannel($channelId)->where('is_suggested', true)
                ->orderBy('suggested_position')->orderBy('id')->get(['id', 'question', 'active']),
        ]);
    }

    public function update(AssistantSettingsRequest $request)
    {
        $this->content->saveProfile($this->channelId(), $request->payload());

        return redirect()->route('admin.assistant.settings.edit')->with('success', __('assistant.saved'));
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
