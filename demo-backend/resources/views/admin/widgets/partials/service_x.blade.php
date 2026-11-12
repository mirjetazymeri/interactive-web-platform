@php
  // Assistant phrases linked to these services by CMS reference (managed under AI Assistant → Synonyms), for the summary
  // per service. Without the assistant tables the editor works as before, without the summary.
  $assistantPhrases = \Illuminate\Support\Facades\Schema::hasTable('assistant_synonyms')
    ? \App\Models\AssistantSynonym::query()->forChannel((int) auth()->user()?->current_channel_id)->whereNotNull('service_refs')->get()
    : null;
@endphp
<div id="cfg-service_x" class="cfg d-none">
  <label class="form-label">Section index or label (optional)</label>
  <input class="form-control mb-3" name="config[section_index]" value="{{ $cfg['section_index'] ?? '' }}">
  <p class="text-muted">Manage this content once for all pages. Choose Slider or Stacked on each page. Lower order values appear first. Save new services before adding their translations.</p>
  <div id="serviceXItems">
    @foreach(($cfg['services'] ?? []) as $serviceIndex => $service)
      @include('admin.widgets.partials.service_x_service')
    @endforeach
  </div>
  <button class="btn btn-outline-primary" type="button" id="addServiceX">Add service</button>
  <template id="serviceXTemplate">
    @include('admin.widgets.partials.service_x_service', ['serviceIndex' => '__SERVICE__', 'service' => []])
  </template>
  <template id="serviceXSubTemplate">
    @include('admin.widgets.partials.service_x_subservice', ['serviceIndex' => '__SERVICE__', 'subIndex' => '__SUB__', 'subservice' => []])
  </template>
</div>
