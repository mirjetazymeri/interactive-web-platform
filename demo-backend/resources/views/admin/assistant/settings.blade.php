@extends('layouts.master')
@section('content')
@php
  $value = fn ($field, $default = null) => old($field, $profile->getAttribute($field) ?? $default);
  $translation = fn ($locale, $field) => old("translations.$locale.$field", optional($profile->translations->firstWhere('locale', $locale))->content[$field] ?? '');
  $fields = [
    'name' => ['input', 100, null],
    'welcome_message' => ['textarea', 1000, null],
    'tooltip' => ['input', 160, null],
    'placeholder' => ['input', 160, null],
    'fallback_message' => ['textarea', 1000, 'fallback_help'],
    'notice' => ['input', 300, 'notice_help'],
  ];
  // Shown as placeholders: what visitors see while a field is empty.
  $defaults = ['name' => 'default_name', 'welcome_message' => 'default_welcome', 'tooltip' => 'default_tooltip',
    'placeholder' => 'default_placeholder', 'fallback_message' => 'fallback', 'notice' => 'default_notice'];
@endphp
@include('admin.assistant._header', ['title' => __('assistant.settings')])

<p class="text-muted">{{ __('assistant.settings_intro') }}</p>

<form method="POST" action="{{ route('admin.assistant.settings.update') }}">
  @csrf @method('PUT')
  <div class="card"><div class="card-body">
    <div class="row g-3 mb-2">
      <div class="col-md-6">
        <div class="form-check form-switch">
          <input type="hidden" name="enabled" value="0">
          <input type="checkbox" class="form-check-input" role="switch" id="assistant-enabled" name="enabled" value="1" @checked($value('enabled', false))>
          <label class="form-check-label fw-semibold" for="assistant-enabled">{{ __('assistant.enabled') }}</label>
        </div>
      </div>
      <div class="col-md-6">
        <div class="form-check form-switch">
          <input type="hidden" name="show_on_mobile" value="0">
          <input type="checkbox" class="form-check-input" role="switch" id="assistant-mobile" name="show_on_mobile" value="1" @checked($value('show_on_mobile', true))>
          <label class="form-check-label" for="assistant-mobile">{{ __('assistant.show_on_mobile') }}</label>
        </div>
      </div>
    </div>

    <h2 class="fs-15 mt-3 mb-3">{{ __('assistant.base_content', ['locale' => config("locales.$defaultLocale.name") ?? strtoupper($defaultLocale)]) }}</h2>
    <div class="row g-3">
      @foreach($fields as $field => [$type, $max, $help])
        <div class="{{ $type === 'textarea' ? 'col-12' : 'col-md-6' }}">
          <label class="form-label" for="profile-{{ $field }}">{{ __('assistant.'.$field) }}</label>
          @if($type === 'textarea')
            <textarea id="profile-{{ $field }}" name="{{ $field }}" class="form-control" rows="2" maxlength="{{ $max }}" placeholder="{{ __('assistant.public.'.$defaults[$field], [], $defaultLocale) }}">{{ $value($field) }}</textarea>
          @else
            <input id="profile-{{ $field }}" name="{{ $field }}" class="form-control" maxlength="{{ $max }}" value="{{ $value($field) }}" placeholder="{{ __('assistant.public.'.$defaults[$field], [], $defaultLocale) }}">
          @endif
          @if($help)<div class="form-text">{{ __('assistant.'.$help) }}</div>@endif
          @error($field)<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      @endforeach
    </div>

    @if($translationLocales)
      <h2 class="fs-15 mt-4 mb-2">{{ __('assistant.translations') }}</h2>
      <p class="text-muted small mb-2">{{ __('assistant.translation_help') }}</p>
      <ul class="nav nav-tabs mb-0 border-bottom-0" role="tablist">
        @foreach($translationLocales as $locale)
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#profile-translation-{{ $locale }}" type="button" role="tab">
              {{ config("locales.$locale.name") ?? strtoupper($locale) }}
            </button>
          </li>
        @endforeach
      </ul>
      <div class="tab-content border rounded-bottom p-3">
        @foreach($translationLocales as $locale)
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="profile-translation-{{ $locale }}" role="tabpanel">
            <div class="row g-3">
              @foreach($fields as $field => [$type, $max, $help])
                <div class="{{ $type === 'textarea' ? 'col-12' : 'col-md-6' }}">
                  <label class="form-label">{{ __('assistant.'.$field) }}</label>
                  @if($type === 'textarea')
                    <textarea name="translations[{{ $locale }}][{{ $field }}]" class="form-control" rows="2" maxlength="{{ $max }}">{{ $translation($locale, $field) }}</textarea>
                  @else
                    <input name="translations[{{ $locale }}][{{ $field }}]" class="form-control" maxlength="{{ $max }}" value="{{ $translation($locale, $field) }}">
                  @endif
                  @error("translations.$locale.$field")<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div></div>

  <div class="card"><div class="card-body">
    <h2 class="fs-15 mb-2">{{ __('assistant.default_suggestions') }}</h2>
    <p class="text-muted small">{{ __('assistant.default_suggestions_help') }}</p>
    @if($suggested->isEmpty())
      <p class="mb-2">{{ __('assistant.no_suggestions') }}</p>
    @else
      <ol class="mb-3">
        @foreach($suggested as $intent)
          <li>{{ $intent->question }} @unless($intent->active)<span class="badge bg-secondary">{{ __('assistant.inactive') }}</span>@endunless</li>
        @endforeach
      </ol>
    @endif
    <a href="{{ route('admin.assistant.suggestions.index') }}" class="btn btn-sm btn-outline-primary">{{ __('assistant.manage_suggestions') }}</a>
  </div></div>

  <div class="card"><div class="card-body">
    <h2 class="fs-15 mb-3">{{ __('assistant.privacy') }}</h2>
    <div class="row g-3">
      <div class="col-md-8">
        <div class="form-check form-switch">
          <input type="hidden" name="log_unanswered" value="0">
          <input type="checkbox" class="form-check-input" role="switch" id="assistant-log" name="log_unanswered" value="1" @checked($value('log_unanswered', true))>
          <label class="form-check-label" for="assistant-log">{{ __('assistant.log_unanswered') }}</label>
        </div>
        <div class="form-text">{{ __('assistant.log_unanswered_help') }}</div>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="assistant-retention">{{ __('assistant.retention_days') }}</label>
        <input type="number" id="assistant-retention" name="unanswered_retention_days" class="form-control" min="7" max="365" required value="{{ $value('unanswered_retention_days', 90) }}">
        @error('unanswered_retention_days')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>
  </div></div>

  @php
    // Admin wordings per language; empty boxes keep the built-in wording, shown as the placeholder.
    $templateText = fn ($locale, $set) => old("response_templates.$locale.$set", implode("\n", (array) ($profile->response_templates[$locale][$set] ?? [])));
    $builtIn = fn ($locale, $set) => implode("\n", (array) trans('assistant.public.'.$set, [], $locale));
  @endphp
  <div class="card"><div class="card-body">
    <details @if($errors->has('response_templates.*')) open @endif>
      <summary class="fs-15 fw-semibold">{{ __('assistant.response_wording') }}</summary>
      <p class="text-muted small mt-2">{{ __('assistant.response_wording_help') }}</p>
      @foreach([$defaultLocale, ...$translationLocales] as $locale)
        <h3 class="fs-14 mt-3">{{ config("locales.$locale.name") ?? strtoupper($locale) }}</h3>
        <div class="row g-3">
          @foreach(\App\Models\AssistantProfile::TEMPLATE_SETS as $set => $placeholders)
            <div class="col-md-6">
              <label class="form-label" for="template-{{ $locale }}-{{ $set }}">{{ __('assistant.template_'.$set) }}</label>
              <textarea id="template-{{ $locale }}-{{ $set }}" name="response_templates[{{ $locale }}][{{ $set }}]" class="form-control" rows="3"
                placeholder="{{ $builtIn($locale, $set) }}">{{ $templateText($locale, $set) }}</textarea>
              <div class="form-text">{{ __('assistant.template_placeholders_help', ['placeholders' => implode(', ', array_map(fn ($p) => ':'.$p, $placeholders)) ?: '—']) }}</div>
              @foreach($errors->get("response_templates.$locale.$set.*") as $messages)
                <div class="text-danger small">{{ implode(' ', $messages) }}</div>
              @endforeach
            </div>
          @endforeach
        </div>
      @endforeach
    </details>
  </div></div>

  <button class="btn btn-primary">{{ __('assistant.save') }}</button>
</form>
@endsection
