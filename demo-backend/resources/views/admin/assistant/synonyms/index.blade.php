@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.synonyms')])

<p class="text-muted">{{ __('assistant.synonyms_intro') }}</p>
@if($service ?? null)
  <div class="alert alert-info py-2">{{ __('assistant.filtered_by_service', ['service' => $services[$service]]) }} <a href="{{ route('admin.assistant.synonyms.index') }}">{{ __('assistant.show_all') }}</a></div>
@endif

<div class="card"><div class="card-body">
  <h2 class="fs-15 mb-3">{{ __('assistant.add_synonyms') }}</h2>
  <form method="POST" action="{{ route('admin.assistant.synonyms.store') }}">
    @csrf
    @include('admin.assistant.synonyms._form')
    <button class="btn btn-primary mt-3">{{ __('assistant.add') }}</button>
  </form>
</div></div>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>{{ __('assistant.terms') }}</th>
          <th>{{ __('assistant.language') }}</th>
          <th>{{ __('assistant.status') }}</th>
          <th class="text-end">{{ __('assistant.actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($synonyms as $group)
        <tr>
          <td>
            @foreach($group->terms ?? [] as $term)
              <span class="badge bg-light text-dark border me-1 mb-1">{{ $term }}</span>
            @endforeach
            @if($group->service_refs || $group->capability)
              <div class="small text-muted mt-1">
                @if($group->capability)
                  <span class="badge {{ ['confirmed' => 'bg-success', 'related' => 'bg-warning text-dark', 'unsupported' => 'bg-secondary'][$group->capability] ?? 'bg-light' }} me-1">{{ __('assistant.capability_'.$group->capability) }}</span>
                @endif
                {{ collect($group->service_refs ?? [])->map(fn ($ref) => $services[$ref] ?? $ref)->implode(', ') }}
              </div>
            @endif
          </td>
          <td>{{ $group->locale ? (config("locales.{$group->locale}.name") ?? strtoupper($group->locale)) : __('assistant.all_languages') }}</td>
          <td>
            @if($group->active)
              <span class="badge bg-success">{{ __('assistant.active') }}</span>
            @else
              <span class="badge bg-secondary">{{ __('assistant.inactive') }}</span>
            @endif
          </td>
          <td class="text-end text-nowrap">
            <a href="{{ route('admin.assistant.synonyms.edit', $group->id) }}" class="btn btn-sm btn-info">{{ __('assistant.edit') }}</a>
            <form method="POST" action="{{ route('admin.assistant.synonyms.destroy', $group->id) }}" class="d-inline" onsubmit="return confirm(@js(__('assistant.confirm_delete_synonyms')))">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">{{ __('assistant.delete') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="4" class="text-center py-4">{{ __('assistant.no_synonyms') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
