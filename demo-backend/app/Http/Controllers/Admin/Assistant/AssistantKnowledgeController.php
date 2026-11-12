<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assistant\AssistantKnowledgeRequest;
use App\Models\AssistantKnowledgeProfile;
use App\Services\Assistant\AssistantContentService;

/** The Company Knowledge Profile: verified company facts, per channel and language. */
class AssistantKnowledgeController extends Controller
{
    public function __construct(private AssistantContentService $content) {}

    public function edit()
    {
        $profile = AssistantKnowledgeProfile::query()->forChannel($this->channelId())->with('translations')->first()
            ?? new AssistantKnowledgeProfile(['channel_id' => $this->channelId()]);

        return view('admin.assistant.knowledge', [
            'profile' => $profile,
            'defaultLocale' => $this->content->defaultLocale(),
            'translationLocales' => $this->content->translationLocales(),
            'limits' => AssistantKnowledgeRequest::LIMITS,
        ]);
    }

    public function update(AssistantKnowledgeRequest $request)
    {
        $this->content->saveKnowledge($this->channelId(), $request->payload());

        return redirect()->route('admin.assistant.knowledge.edit')->with('success', __('assistant.saved'));
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
