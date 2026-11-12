<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Services\Assistant\AssistantContentService;
use App\Services\Assistant\AssistantEngine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Test console: the public engine with matching details, nothing recorded. */
class AssistantTestController extends Controller
{
    public function __construct(private AssistantEngine $engine, private AssistantContentService $content) {}

    public function show()
    {
        $channelId = $this->channelId();

        return view('admin.assistant.test', [
            'locales' => $this->locales(),
            'enabled' => $this->engine->enabled($channelId, $this->content->defaultLocale()),
        ]);
    }

    public function run(Request $request)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:'.(int) config('assistant.max_length', 300)],
            'locale' => ['required', Rule::in($this->locales())],
            'context' => ['nullable', 'array'],
        ]);

        return response()->json($this->engine->reply($this->channelId(), $data['locale'], $data['message'], (array) ($data['context'] ?? []), null, true));
    }

    private function locales(): array
    {
        return [$this->content->defaultLocale(), ...$this->content->translationLocales()];
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
