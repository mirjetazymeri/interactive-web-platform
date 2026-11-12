@php
  $value = fn ($field, $default = null) => old($field, $intent->getAttribute($field) ?? $default);
  $lines = fn ($field) => old($field) !== null ? (is_array(old($field)) ? implode("\n", old($field)) : old($field)) : implode("\n", (array) ($intent->getAttribute($field) ?? []));
  $translation = function ($locale, $field) use ($intent) {
      $old = old("translations.$locale.$field");
      if ($old !== null) {
          return is_array($old) ? implode("\n", $old) : $old;
      }
      $stored = optional($intent->translations?->firstWhere('locale', $locale))->content[$field] ?? '';

      return is_array($stored) ? implode("\n", $stored) : $stored;
  };
  $selectedFollowups = collect(old('followups', $intent->exists ? $intent->followups->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all();
  $selectedSource = old('source', $source);
  $prefill = $unanswered?->question;
  $prefillDefault = $prefill && $unanswered->locale === $defaultLocale;
  $advancedOpen = $errors->has('negative_keywords*') || $lines('negative_keywords') !== '' || (int) $value('priority', 0) !== 0;
@endphp

@if($unanswered)
  <input type="hidden" name="from_unanswered" value="{{ $unanswered->id }}">
  <div class="alert alert-info small">{{ __('assistant.created_from_unanswered') }} “{{ $unanswered->question }}” ({{ strtoupper($unanswered->locale) }})</div>
@endif

<div class="row g-3">
  <div class="col-lg-8">
    <label class="form-label" for="intent-question">{{ __('assistant.question') }} *</label>
    <input id="intent-question" name="question" class="form-control" required maxlength="255" value="{{ $value('question', $prefillDefault ? $prefill : null) }}">
    <div class="form-text">{{ __('assistant.question_help') }}</div>
  </div>
  <div class="col-lg-4">
    <label class="form-label" for="intent-category">{{ __('assistant.category') }}</label>
    <select id="intent-category" name="assistant_category_id" class="form-select">
      <option value="">{{ __('assistant.no_category') }}</option>
      @foreach($categories as $category)
        <option value="{{ $category->id }}" @selected((int) $value('assistant_category_id') === $category->id)>{{ $category->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-12">
    <label class="form-label" for="intent-answer">{{ __('assistant.answer') }} *</label>
    <textarea id="intent-answer" name="answer" class="form-control" rows="4" required maxlength="3000">{{ $value('answer') }}</textarea>
    <div class="form-text">{{ __('assistant.answer_help') }}</div>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="intent-phrasings">{{ __('assistant.phrasings') }}</label>
    <textarea id="intent-phrasings" name="phrasings" class="form-control" rows="4">{{ $lines('phrasings') }}</textarea>
    <div class="form-text">{{ __('assistant.phrasings_help') }}</div>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="intent-keywords">{{ __('assistant.keywords') }}</label>
    <textarea id="intent-keywords" name="keywords" class="form-control" rows="4">{{ $lines('keywords') }}</textarea>
    <div class="form-text">{{ __('assistant.keywords_help') }}</div>
  </div>
  <div class="col-md-6 d-flex flex-column gap-2 justify-content-end">
    <div class="form-check">
      <input type="hidden" name="active" value="0">
      <input type="checkbox" class="form-check-input" id="intent-active" name="active" value="1" @checked($value('active', true))>
      <label class="form-check-label" for="intent-active">{{ __('assistant.active') }}</label>
    </div>
    <div class="form-check">
      <input type="hidden" name="is_suggested" value="0">
      <input type="checkbox" class="form-check-input" id="intent-suggested" name="is_suggested" value="1" @checked($value('is_suggested', false))>
      <label class="form-check-label" for="intent-suggested">{{ __('assistant.is_suggested') }}</label>
    </div>
  </div>
</div>

<h2 class="fs-15 mt-4 mb-3">{{ __('assistant.related_content') }}</h2>
<div class="row g-3">
  <div class="col-lg-6">
    <label class="form-label" for="intent-source">{{ __('assistant.related_content') }}</label>
    <select id="intent-source" name="source" class="form-select">
      <option value="">{{ __('assistant.source_none') }}</option>
      @foreach($sources['general'] as $key => $label)
        <option value="{{ $key }}" @selected($selectedSource === $key)>{{ $label }}</option>
      @endforeach
      @if($sources['services'])
        <optgroup label="{{ __('assistant.source_group_services') }}">
          @foreach($sources['services'] as $key => $label)
            <option value="{{ $key }}" @selected($selectedSource === $key)>{{ $label }}</option>
          @endforeach
        </optgroup>
      @endif
      @if($sources['projects'])
        <optgroup label="{{ __('assistant.source_group_projects') }}">
          @foreach($sources['projects'] as $key => $label)
            <option value="{{ $key }}" @selected($selectedSource === $key)>{{ $label }}</option>
          @endforeach
        </optgroup>
      @endif
      @if($selectedSource && ! isset($sources['general'][$selectedSource]) && ! isset($sources['services'][$selectedSource]) && ! isset($sources['projects'][$selectedSource]))
        <option value="{{ $selectedSource }}" selected>{{ __('assistant.missing_source') }}</option>
      @endif
    </select>
    <div class="form-text">{{ __('assistant.related_content_help') }}</div>
  </div>
  <div class="col-lg-6">
    <label class="form-label" for="intent-followups">{{ __('assistant.followups') }}</label>
    <select id="intent-followups" name="followups[]" class="form-select" multiple size="4" data-assistant-select2>
      @foreach($others as $other)
        <option value="{{ $other->id }}" @selected(in_array($other->id, $selectedFollowups, true))>{{ $other->question }}@unless($other->active) ({{ __('assistant.inactive') }})@endunless</option>
      @endforeach
    </select>
    <div class="form-text">{{ __('assistant.followups_help') }}</div>
  </div>
  <div class="col-lg-5">
    <label class="form-label" for="intent-page">{{ __('assistant.related_page') }}</label>
    <select id="intent-page" name="page_id" class="form-select">
      <option value="">{{ __('assistant.none') }}</option>
      @foreach($pages as $id => $label)
        <option value="{{ $id }}" @selected((int) $value('page_id') === $id)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-lg-3">
    <label class="form-label" for="intent-anchor">{{ __('assistant.page_anchor') }}</label>
    <input id="intent-anchor" name="page_anchor" class="form-control" maxlength="100" value="{{ $value('page_anchor') }}" pattern="[A-Za-z][A-Za-z0-9_-]*">
    <div class="form-text">{{ __('assistant.page_anchor_help') }}</div>
  </div>
  <div class="col-lg-4">
    <label class="form-label" for="intent-link-label">{{ __('assistant.link_label') }}</label>
    <input id="intent-link-label" name="link_label" class="form-control" maxlength="80" value="{{ $value('link_label') }}">
    <div class="form-text">{{ __('assistant.link_label_help') }}</div>
  </div>
</div>

<details class="mt-4 border rounded p-3" @if($advancedOpen) open @endif>
  <summary class="fw-semibold">{{ __('assistant.advanced') }}</summary>
  <div class="row g-3 mt-1">
    <div class="col-md-8">
      <label class="form-label" for="intent-negative">{{ __('assistant.negative_keywords') }}</label>
      <textarea id="intent-negative" name="negative_keywords" class="form-control" rows="3">{{ $lines('negative_keywords') }}</textarea>
      <div class="form-text">{{ __('assistant.negative_keywords_help') }}</div>
    </div>
    <div class="col-md-4">
      <label class="form-label" for="intent-priority">{{ __('assistant.priority') }}</label>
      <select id="intent-priority" name="priority" class="form-select">
        @foreach([-1 => 'priority_low', 0 => 'priority_normal', 1 => 'priority_high'] as $priority => $label)
          <option value="{{ $priority }}" @selected((int) $value('priority', 0) === $priority)>{{ __('assistant.'.$label) }}</option>
        @endforeach
      </select>
    </div>
  </div>
</details>

@if($translationLocales)
  <h2 class="fs-15 mt-4 mb-2">{{ __('assistant.translations') }}</h2>
  <p class="text-muted small mb-2">{{ __('assistant.translation_help') }}</p>
  <ul class="nav nav-tabs mb-0 border-bottom-0" role="tablist">
    @foreach($translationLocales as $locale)
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#intent-translation-{{ $locale }}" type="button" role="tab">
          {{ config("locales.$locale.name") ?? strtoupper($locale) }}
        </button>
      </li>
    @endforeach
  </ul>
  <div class="tab-content border rounded-bottom p-3">
    @foreach($translationLocales as $locale)
      @php($prefillHere = $prefill && $unanswered->locale === $locale)
      <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="intent-translation-{{ $locale }}" role="tabpanel">
        <div class="row g-3">
          <div class="col-lg-8">
            <label class="form-label">{{ __('assistant.question') }}</label>
            <input name="translations[{{ $locale }}][question]" class="form-control" maxlength="255" value="{{ $translation($locale, 'question') ?: ($prefillHere ? $prefill : '') }}">
          </div>
          <div class="col-lg-4">
            <label class="form-label">{{ __('assistant.link_label') }}</label>
            <input name="translations[{{ $locale }}][link_label]" class="form-control" maxlength="80" value="{{ $translation($locale, 'link_label') }}">
          </div>
          <div class="col-12">
            <label class="form-label">{{ __('assistant.answer') }}</label>
            <textarea name="translations[{{ $locale }}][answer]" class="form-control" rows="3" maxlength="3000">{{ $translation($locale, 'answer') }}</textarea>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('assistant.phrasings') }}</label>
            <textarea name="translations[{{ $locale }}][phrasings]" class="form-control" rows="3">{{ $translation($locale, 'phrasings') }}</textarea>
            <div class="form-text">{{ __('assistant.one_per_line') }}</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('assistant.keywords') }}</label>
            <textarea name="translations[{{ $locale }}][keywords]" class="form-control" rows="3">{{ $translation($locale, 'keywords') }}</textarea>
            <div class="form-text">{{ __('assistant.one_per_line') }}</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">{{ __('assistant.negative_keywords') }}</label>
            <textarea name="translations[{{ $locale }}][negative_keywords]" class="form-control" rows="3">{{ $translation($locale, 'negative_keywords') }}</textarea>
            <div class="form-text">{{ __('assistant.one_per_line') }}</div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif

@section('scripts')
@parent
<script>
  // Searchable follow-up picker when select2 is loaded; a plain multi-select otherwise.
  if (window.jQuery && jQuery.fn.select2) {
    jQuery('[data-assistant-select2]').select2({ width: '100%' });
  }
</script>
@endsection
