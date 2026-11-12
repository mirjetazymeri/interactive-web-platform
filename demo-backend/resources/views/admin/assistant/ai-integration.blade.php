@extends('layouts.master')
@section('content')
@php
  $value = fn ($field, $default = null) => old($field, $settings->getAttribute($field) ?? $default);
  $strategy = $value('strategy', 'deterministic');
  $strategies = ['deterministic', 'hybrid', 'generative'];
@endphp
@include('admin.assistant._header', ['title' => __('assistant.ai_integration')])

<p class="text-muted">{{ __('assistant.ai_intro') }}</p>

<div class="row g-3 mb-1">
  <div class="col-md-6">
    <div class="card h-100 border-success"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="fs-15 mb-0">{{ __('assistant.ai_current_engine') }}</h2>
        <span class="badge bg-success">{{ __('assistant.ai_state_active') }}</span>
      </div>
      <p class="mb-1 fw-semibold">{{ __('assistant.ai_strategy_deterministic') }}</p>
      <p class="text-muted small mb-0">{{ __('assistant.ai_current_engine_text') }}</p>
    </div></div>
  </div>
  <div class="col-md-6">
    <div class="card h-100"><div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="fs-15 mb-0">{{ __('assistant.ai_future') }}</h2>
        <span class="badge bg-secondary">{{ __('assistant.ai_state_disabled') }}</span>
      </div>
      <p class="text-muted small mb-1">{{ __('assistant.ai_future_text') }}</p>
      @unless($canEnable)<p class="small mb-0"><i class="ri-information-line"></i> {{ __('assistant.ai_no_adapter') }}</p>@endunless
    </div></div>
  </div>
</div>

<form method="POST" action="{{ route('admin.assistant.ai.update') }}" autocomplete="off">
  @csrf @method('PUT')

  <div class="card"><div class="card-body">
    <h2 class="fs-15 mb-3">{{ __('assistant.ai_general') }}</h2>
    <div class="form-check form-switch mb-3">
      <input type="hidden" name="enabled" value="0">
      <input type="checkbox" class="form-check-input" role="switch" id="ai-enabled" name="enabled" value="1" @checked($value('enabled', false)) @disabled(! $canEnable) aria-describedby="ai-enabled-help">
      <label class="form-check-label" for="ai-enabled">{{ __('assistant.ai_enabled') }}</label>
      <div class="form-text" id="ai-enabled-help">@unless($canEnable){{ __('assistant.ai_no_adapter') }}@endunless</div>
      @error('enabled')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label" for="ai-provider">{{ __('assistant.ai_provider') }}</label>
        <select id="ai-provider" name="provider" class="form-select">
          <option value="">{{ __('assistant.ai_provider_none') }}</option>
          @foreach($providers as $key => $label)
            <option value="{{ $key }}" @selected($value('provider') === $key)>{{ $label }}</option>
          @endforeach
        </select>
        @error('provider')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label class="form-label" for="ai-model">{{ __('assistant.ai_model') }}</label>
        <input id="ai-model" name="model" class="form-control" maxlength="120" value="{{ $value('model') }}" list="ai-models" spellcheck="false">
        <datalist id="ai-models">@foreach($models as $model)<option value="{{ $model }}">@endforeach</datalist>
        <div class="form-text">{{ __('assistant.ai_model_help') }}</div>
        @error('model')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label class="form-label" for="ai-endpoint">{{ __('assistant.ai_endpoint') }}</label>
        <input id="ai-endpoint" name="endpoint_url" type="url" class="form-control" maxlength="255" value="{{ $value('endpoint_url') }}" placeholder="https://" spellcheck="false">
        @error('endpoint_url')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
    </div>

    <fieldset class="mt-4">
      <legend class="form-label fs-14 mb-2">{{ __('assistant.ai_strategy') }}</legend>
      <div class="row g-2">
        @foreach($strategies as $option)
          @php($usable = in_array($option, $available, true))
          <div class="col-md-4">
            <label class="border rounded p-3 d-block h-100 {{ $usable ? '' : 'opacity-50' }}" for="ai-strategy-{{ $option }}">
              <span class="form-check mb-1">
                <input class="form-check-input" type="radio" name="strategy" id="ai-strategy-{{ $option }}" value="{{ $option }}" @checked($strategy === $option) @disabled(! $usable)>
                <span class="form-check-label fw-semibold">{{ __('assistant.ai_strategy_'.$option) }}</span>
              </span>
              <span class="d-block text-muted small">{{ __('assistant.ai_strategy_'.$option.'_help') }}</span>
              @unless($usable)<span class="badge bg-light text-dark border mt-2">{{ __('assistant.ai_unavailable') }}</span>@endunless
            </label>
          </div>
        @endforeach
      </div>
      @error('strategy')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </fieldset>
  </div></div>

  <div class="card"><div class="card-body">
    <h2 class="fs-15 mb-1">{{ __('assistant.ai_credentials') }}</h2>
    <p class="text-muted small">{{ __('assistant.ai_credentials_help') }}</p>
    <p class="mb-3">
      @if($settings->hasApiKey())
        <span class="badge bg-info-transparent text-info"><i class="ri-lock-line"></i> {{ __('assistant.ai_key_stored', ['hint' => $settings->api_key_hint, 'date' => $settings->api_key_updated_at?->format('Y-m-d')]) }}</span>
      @else
        <span class="badge bg-light text-dark border">{{ __('assistant.ai_key_none') }}</span>
      @endif
    </p>
    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label" for="ai-key">{{ __('assistant.ai_key_new') }}</label>
        {{-- Never pre-filled: the stored key is not sent back to the browser. --}}
        <input id="ai-key" name="api_key" type="password" class="form-control" autocomplete="new-password" spellcheck="false" maxlength="500" value="">
        <div class="form-text">{{ __('assistant.ai_key_new_help') }}</div>
        @error('api_key')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      @if($settings->hasApiKey())
        <div class="col-md-6">
          <div class="form-check mb-2">
            <input type="hidden" name="remove_api_key" value="0">
            <input type="checkbox" class="form-check-input" id="ai-key-remove" name="remove_api_key" value="1">
            <label class="form-check-label" for="ai-key-remove">{{ __('assistant.ai_key_remove') }}</label>
          </div>
        </div>
      @endif
    </div>
  </div></div>

  <div class="card"><div class="card-body">
    <h2 class="fs-15 mb-1">{{ __('assistant.ai_limits') }}</h2>
    <p class="text-muted small">{{ __('assistant.ai_limits_help') }}</p>
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label" for="ai-requests">{{ __('assistant.ai_request_limit') }}</label>
        <input id="ai-requests" name="monthly_request_limit" type="number" min="1" step="1" class="form-control" value="{{ $value('monthly_request_limit') }}" placeholder="{{ __('assistant.ai_no_limit') }}">
        @error('monthly_request_limit')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label class="form-label" for="ai-tokens">{{ __('assistant.ai_token_limit') }}</label>
        <input id="ai-tokens" name="monthly_token_limit" type="number" min="1" step="1" class="form-control" value="{{ $value('monthly_token_limit') }}" placeholder="{{ __('assistant.ai_no_limit') }}">
        @error('monthly_token_limit')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label class="form-label" for="ai-spend">{{ __('assistant.ai_spend_limit') }}</label>
        <input id="ai-spend" name="monthly_spend_limit" type="number" min="0" step="0.01" class="form-control" value="{{ $value('monthly_spend_limit') }}" placeholder="{{ __('assistant.ai_no_limit') }}">
        @error('monthly_spend_limit')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label class="form-label" for="ai-currency">{{ __('assistant.ai_currency') }}</label>
        <select id="ai-currency" name="spend_currency" class="form-select">
          @foreach($currencies as $currency)
            <option value="{{ $currency }}" @selected($value('spend_currency', 'EUR') === $currency)>{{ $currency }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12">
        <div class="form-check">
          <input type="hidden" name="disable_on_limit" value="0">
          <input type="checkbox" class="form-check-input" id="ai-disable-on-limit" name="disable_on_limit" value="1" @checked($value('disable_on_limit', true))>
          <label class="form-check-label" for="ai-disable-on-limit">{{ __('assistant.ai_disable_on_limit') }}</label>
        </div>
      </div>
    </div>
  </div></div>

  <button class="btn btn-primary">{{ __('assistant.save') }}</button>
</form>
@endsection
