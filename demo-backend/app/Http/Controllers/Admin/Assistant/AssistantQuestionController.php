<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assistant\AssistantIntentRequest;
use App\Models\AssistantCategory;
use App\Models\AssistantIntent;
use App\Models\AssistantUnansweredQuestion;
use App\Services\Assistant\AssistantContentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssistantQuestionController extends Controller
{
    public function __construct(private AssistantContentService $content) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $search = trim((string) ($filters['q'] ?? ''));
        $category = $filters['category'] ?? '';

        $questions = AssistantIntent::query()->forChannel($this->channelId())
            ->with(['category', 'translations'])
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
                $query->where(fn ($q) => $q->where('question', 'like', $like)->orWhere('answer', 'like', $like)
                    ->orWhereHas('translations', fn ($t) => $t->where('content', 'like', $like)));
            })
            ->when($category === 'none', fn ($q) => $q->whereNull('assistant_category_id'))
            ->when(ctype_digit($category), fn ($q) => $q->where('assistant_category_id', (int) $category))
            ->when(($filters['status'] ?? '') !== '', fn ($q) => $q->where('active', $filters['status'] === 'active'))
            ->orderBy('position')->orderBy('id')
            ->get();

        return view('admin.assistant.questions.index', [
            'questions' => $questions,
            'categories' => $this->categories(),
            'filters' => ['q' => $search, 'category' => $category, 'status' => $filters['status'] ?? ''],
            'filtered' => $search !== '' || $category !== '' || ($filters['status'] ?? '') !== '',
        ]);
    }

    public function create(Request $request)
    {
        $intent = new AssistantIntent(['active' => true, 'priority' => 0]);
        $unanswered = null;
        if ($request->filled('from')) {
            $unanswered = AssistantUnansweredQuestion::query()->forChannel($this->channelId())->find((int) $request->input('from'));
        }

        return view('admin.assistant.questions.create', $this->formData($intent) + ['unanswered' => $unanswered]);
    }

    public function store(AssistantIntentRequest $request)
    {
        $intent = $this->content->saveIntent(null, $request->payload(), $this->channelId());

        return redirect()->route('admin.assistant.questions.edit', $intent->id)->with('success', __('assistant.saved'));
    }

    public function edit(int $question)
    {
        return view('admin.assistant.questions.edit', $this->formData($this->find($question)->load(['translations', 'followups'])) + ['unanswered' => null]);
    }

    public function update(AssistantIntentRequest $request, int $question)
    {
        $intent = $this->content->saveIntent($this->find($question), $request->payload(), $this->channelId());

        return redirect()->route('admin.assistant.questions.edit', $intent->id)->with('success', __('assistant.saved'));
    }

    public function destroy(int $question)
    {
        $this->find($question)->delete();

        return redirect()->route('admin.assistant.questions.index')->with('success', __('assistant.deleted'));
    }

    public function reorder(Request $request)
    {
        $data = $request->validate(['ordering' => ['required', 'array', 'min:1'], 'ordering.*' => ['integer']]);
        DB::transaction(function () use ($data) {
            foreach ($data['ordering'] as $position => $id) {
                $this->find((int) $id)->update(['position' => $position + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function formData(AssistantIntent $intent): array
    {
        $channelId = $this->channelId();

        return [
            'intent' => $intent,
            'categories' => $this->categories(),
            'sources' => $this->content->sourceOptions($channelId),
            'source' => AssistantContentService::joinSource($intent->source_type, $intent->source_id),
            'pages' => $this->content->pageOptions($channelId),
            'others' => AssistantIntent::query()->forChannel($channelId)->when($intent->exists, fn ($q) => $q->whereKeyNot($intent->id))
                ->orderBy('position')->orderBy('id')->get(['id', 'question', 'active']),
            'translationLocales' => $this->content->translationLocales(),
            'defaultLocale' => $this->content->defaultLocale(),
        ];
    }

    private function categories()
    {
        return AssistantCategory::query()->forChannel($this->channelId())->orderBy('position')->orderBy('name')->get();
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
