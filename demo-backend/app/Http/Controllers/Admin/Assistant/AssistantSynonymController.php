<?php

namespace App\Http\Controllers\Admin\Assistant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Assistant\AssistantSynonymRequest;
use App\Models\AssistantSynonym;
use App\Services\Assistant\AssistantContentService;
use Illuminate\Http\Request;

class AssistantSynonymController extends Controller
{
    public function __construct(private AssistantContentService $content) {}

    public function index(Request $request)
    {
        $services = $this->content->serviceRefOptions($this->channelId());
        // From a service's "Assistant knowledge" panel: only phrases for that service and its subservices.
        $service = is_string($request->query('service')) && isset($services[$request->query('service')]) ? $request->query('service') : null;
        $synonyms = AssistantSynonym::query()->forChannel($this->channelId())->orderBy('locale')->orderBy('id')->get();
        if ($service) {
            $synonyms = $synonyms->filter(fn (AssistantSynonym $synonym) => collect($synonym->service_refs ?? [])
                ->contains(fn ($ref) => $ref === $service || str_starts_with((string) $ref, $service.'/')))->values();
        }

        return view('admin.assistant.synonyms.index', [
            'synonyms' => $synonyms,
            'locales' => $this->locales(),
            'synonym' => new AssistantSynonym(['active' => true, 'service_refs' => $service ? [$service] : null]),
            'services' => $services,
            'service' => $service,
        ]);
    }

    public function store(AssistantSynonymRequest $request)
    {
        $this->content->saveSynonym(null, $request->validated(), $this->channelId());

        return redirect()->route('admin.assistant.synonyms.index')->with('success', __('assistant.saved'));
    }

    public function edit(int $synonym)
    {
        return view('admin.assistant.synonyms.edit', ['synonym' => $this->find($synonym), 'locales' => $this->locales(),
            'services' => $this->content->serviceRefOptions($this->channelId())]);
    }

    public function update(AssistantSynonymRequest $request, int $synonym)
    {
        $this->content->saveSynonym($this->find($synonym), $request->validated(), $this->channelId());

        return redirect()->route('admin.assistant.synonyms.index')->with('success', __('assistant.saved'));
    }

    public function destroy(int $synonym)
    {
        $this->find($synonym)->delete();

        return redirect()->route('admin.assistant.synonyms.index')->with('success', __('assistant.deleted'));
    }

    private function locales(): array
    {
        return [$this->content->defaultLocale(), ...$this->content->translationLocales()];
    }

    private function find(int $id): AssistantSynonym
    {
        return AssistantSynonym::query()->forChannel($this->channelId())->findOrFail($id);
    }

    private function channelId(): int
    {
        return (int) auth()->user()->current_channel_id;
    }
}
