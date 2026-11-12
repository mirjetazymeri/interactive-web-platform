<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assistant\AssistantCategoryRequest;
use App\Models\AssistantCategory;
use App\Services\Assistant\AssistantContentService;

class AssistantCategoryController extends Controller
{
    public function __construct(private AssistantContentService $content) {}

    public function index()
    {
        $categories = AssistantCategory::query()->forChannel($this->channelId())->withCount('intents')
            ->orderBy('position')->orderBy('name')->get();

        return view('admin.assistant.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.assistant.categories.create', $this->formData(new AssistantCategory(['active' => true])));
    }

    public function store(AssistantCategoryRequest $request)
    {
        $this->content->saveCategory(null, $request->payload(), $this->channelId());

        return redirect()->route('admin.assistant.categories.index')->with('success', __('assistant.saved'));
    }

    public function edit(int $category)
    {
        return view('admin.assistant.categories.edit', $this->formData($this->find($category)->load('translations')));
    }

    public function update(AssistantCategoryRequest $request, int $category)
    {
        $this->content->saveCategory($this->find($category), $request->payload(), $this->channelId());

        return redirect()->route('admin.assistant.categories.index')->with('success', __('assistant.saved'));
    }

    public function destroy(int $category)
    {
        $this->find($category)->delete();

        return redirect()->route('admin.assistant.categories.index')->with('success', __('assistant.deleted'));
    }

    private function formData(AssistantCategory $category): array
    {
        return ['category' => $category, 'translationLocales' => $this->content->translationLocales(), 'defaultLocale' => $this->content->defaultLocale()];
    }

    private function find(int $id): AssistantCategory
    {
        return AssistantCategory::query()->forChannel($this->channelId())->findOrFail($id);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
