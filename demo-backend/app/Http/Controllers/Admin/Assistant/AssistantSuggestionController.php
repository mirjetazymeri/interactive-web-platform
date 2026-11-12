<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Models\AssistantIntent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** The ordered list of questions offered when the chat opens. */
class AssistantSuggestionController extends Controller
{
    public function index()
    {
        $all = AssistantIntent::query()->forChannel($this->channelId())->orderBy('position')->orderBy('id')->get(['id', 'question', 'active', 'is_suggested', 'suggested_position']);

        return view('admin.assistant.suggestions', [
            'suggested' => $all->where('is_suggested', true)->sortBy([['suggested_position', 'asc'], ['id', 'asc']])->values(),
            'available' => $all->where('is_suggested', false)->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['intent_id' => ['required', 'integer']]);
        $position = (int) AssistantIntent::query()->forChannel($this->channelId())->max('suggested_position') + 1;
        $this->find($data['intent_id'])->update(['is_suggested' => true, 'suggested_position' => $position]);

        return redirect()->route('admin.assistant.suggestions.index')->with('success', __('assistant.saved'));
    }

    public function destroy(int $suggestion)
    {
        $this->find($suggestion)->update(['is_suggested' => false]);

        return redirect()->route('admin.assistant.suggestions.index')->with('success', __('assistant.saved'));
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['ordering' => ['required', 'array', 'min:1'], 'ordering.*' => ['integer']]);
        DB::transaction(function () use ($data) {
            foreach ($data['ordering'] as $position => $id) {
                $this->find((int) $id)->update(['suggested_position' => $position + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function find(int $id): AssistantIntent
    {
        return AssistantIntent::query()->forChannel($this->channelId())->findOrFail($id);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
