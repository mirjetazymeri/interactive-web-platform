<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Models\AssistantIntent;
use App\Models\AssistantUnansweredQuestion;
use App\Services\Assistant\AssistantContentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssistantUnansweredController extends Controller
{
    public function __construct(private AssistantContentService $content) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'all', ...AssistantUnansweredQuestion::STATUSES])],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);
        $status = $filters['status'] ?? 'open';
        $channelId = $this->channelId();

        $questions = AssistantUnansweredQuestion::query()->forChannel($channelId)
            ->with('resolvedIntent:id,question')
            ->when($status === 'open', fn ($q) => $q->whereIn('status', ['new', 'reviewed']))
            ->when(! in_array($status, ['open', 'all'], true), fn ($q) => $q->where('status', $status))
            ->when($filters['locale'] ?? null, fn ($q, $locale) => $q->where('locale', $locale))
            ->orderByDesc('occurrences')->orderByDesc('last_seen_at')
            ->paginate(50)->withQueryString();

        return view('admin.assistant.unanswered', [
            'questions' => $questions,
            'filters' => ['status' => $status, 'locale' => $filters['locale'] ?? ''],
            'locales' => [$this->content->defaultLocale(), ...$this->content->translationLocales()],
            'intents' => AssistantIntent::query()->forChannel($channelId)->orderBy('position')->orderBy('id')->get(['id', 'question']),
            'retention' => $this->content->profile($channelId)->unanswered_retention_days ?? 90,
        ]);
    }

    public function status(Request $request, int $question)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'reviewed', 'ignored'])]]);
        $this->find($question)->update(['status' => $data['status']]);

        return back()->with('success', __('assistant.saved'));
    }

    public function attach(Request $request, int $question)
    {
        $data = $request->validate(['intent_id' => ['required', 'integer']]);
        $intent = AssistantIntent::query()->forChannel($this->channelId())->findOrFail($data['intent_id']);
        $this->content->addPhrasing($intent, $this->find($question));

        return back()->with('success', __('assistant.phrasing_added'));
    }

    public function destroy(int $question)
    {
        $this->find($question)->delete();

        return back()->with('success', __('assistant.deleted'));
    }

    private function find(int $id): AssistantUnansweredQuestion
    {
        return AssistantUnansweredQuestion::query()->forChannel($this->channelId())->findOrFail($id);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
