@extends('layouts.master')
@section('content')
@php
  $value = fn ($field) => old($field, $profile->getAttribute($field));
  $lines = fn ($field) => old($field) !== null ? (is_array(old($field)) ? implode("\n", old($field)) : old($field)) : implode("\n", (array) ($profile->getAttribute($field) ?? []));
  $translation = function ($locale, $field) use ($profile) {
      $old = old("translations.$locale.$field");
      if ($old !== null) {
          return is_array($old) ? implode("\n", $old) : $old;
      }
      $stored = optional($profile->translations?->firstWhere('locale', $locale))->content[$field] ?? '';

      return is_array($stored) ? implode("\n", $stored) : $stored;
  };
  $languages = (array) old('languages', $profile->languages ?? []);
  $fields = [
    'introduction' => ['knowledge_introduction', 2, 'knowledge_introduction_help'],
    'description' => ['knowledge_description', 5, null],
    'expertise' => ['knowledge_expertise', 3, 'knowledge_reserved'],
    'mission' => ['knowledge_mission', 3, 'knowledge_reserved'],
    'additional_information' => ['knowledge_additional', 3, 'knowledge_additional_help'],
  ];
@endphp
@include('admin.assistant._header', ['title' => __('assistant.knowledge')])

<p class="text-muted">{{ __('assistant.knowledge_intro') }}</p>

<form method="POST" action="{{ route('admin.assistant.knowledge.update') }}">
  @csrf @method('PUT')
  <div class="card"><div class="card-body">
    <div class="row g-3 mb-2">
      <div class="col-md-3">
        <label class="form-label" for="knowledge-status">{{ __('assistant.knowledge_status') }}</label>
        <select id="knowledge-status" name="status" class="form-select">
          @foreach(\App\Models\AssistantKnowledgeProfile::STATUSES as $status)
            <option value="{{ $status }}" @selected($value('status') === $status)>{{ __('assistant.status_'.$status) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-9">
        <span class="form-label d-block">{{ __('assistant.knowledge_languages') }}</span>
        @foreach([$defaultLocale, ...$translationLocales] as $locale)
          <div class="form-check form-check-inline mt-1">
            <input class="form-check-input" type="checkbox" name="languages[]" id="knowledge-lang-{{ $locale }}" value="{{ $locale }}" @checked(in_array($locale, $languages, true))>
            <label class="form-check-label" for="knowledge-lang-{{ $locale }}">{{ config("locales.$locale.name") ?? strtoupper($locale) }}</label>
          </div>
        @endforeach
        @error('languages.*')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>

    <h2 class="fs-15 mt-3 mb-3">{{ __('assistant.base_content', ['locale' => config("locales.$defaultLocale.name") ?? strtoupper($defaultLocale)]) }}</h2>
    <div class="row g-3">
      @foreach($fields as $field => [$label, $rows, $help])
        <div class="col-12">
          <label class="form-label" for="knowledge-{{ $field }}">{{ __('assistant.'.$label) }}</label>
          <textarea id="knowledge-{{ $field }}" name="{{ $field }}" class="form-control" rows="{{ $rows }}" maxlength="{{ $limits[$field] }}">{{ $value($field) }}</textarea>
          @if($help)<div class="form-text">{{ __('assistant.'.$help) }}</div>@endif
          @error($field)<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
      @endforeach
      <div class="col-12">
        <label class="form-label" for="knowledge-capabilities">{{ __('assistant.knowledge_capabilities') }}</label>
        <textarea id="knowledge-capabilities" name="capabilities" class="form-control" rows="4">{{ $lines('capabilities') }}</textarea>
        <div class="form-text">{{ __('assistant.one_per_line') }} {{ __('assistant.knowledge_reserved') }}</div>
        @error('capabilities*')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>

    @if($translationLocales)
      <h2 class="fs-15 mt-4 mb-2">{{ __('assistant.translations') }}</h2>
      <p class="text-muted small mb-2">{{ __('assistant.translation_help') }}</p>
      <ul class="nav nav-tabs mb-0 border-bottom-0" role="tablist">
        @foreach($translationLocales as $locale)
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#knowledge-translation-{{ $locale }}" type="button" role="tab">
              {{ config("locales.$locale.name") ?? strtoupper($locale) }}
            </button>
          </li>
        @endforeach
      </ul>
      <div class="tab-content border rounded-bottom p-3">
        @foreach($translationLocales as $locale)
          <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="knowledge-translation-{{ $locale }}" role="tabpanel">
            <div class="row g-3">
              @foreach($fields as $field => [$label, $rows, $help])
                <div class="col-12">
                  <label class="form-label">{{ __('assistant.'.$label) }}</label>
                  <textarea name="translations[{{ $locale }}][{{ $field }}]" class="form-control" rows="{{ $rows }}" maxlength="{{ $limits[$field] }}">{{ $translation($locale, $field) }}</textarea>
                  @error("translations.$locale.$field")<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
              @endforeach
              <div class="col-12">
                <label class="form-label">{{ __('assistant.knowledge_capabilities') }}</label>
                <textarea name="translations[{{ $locale }}][capabilities]" class="form-control" rows="3">{{ $translation($locale, 'capabilities') }}</textarea>
                <div class="form-text">{{ __('assistant.one_per_line') }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div></div>

  <button class="btn btn-primary">{{ __('assistant.save') }}</button>
</form>
@endsection
