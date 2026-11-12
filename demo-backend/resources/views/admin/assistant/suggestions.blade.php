@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.suggestions')])

<p class="text-muted">{{ __('assistant.suggestions_intro') }}</p>

@if($available->isNotEmpty())
  <form method="POST" action="{{ route('admin.assistant.suggestions.store') }}" class="row g-2 align-items-end mb-3">
    @csrf
    <div class="col-md-8">
      <label class="form-label small mb-1" for="suggestion-intent">{{ __('assistant.choose_question') }}</label>
      <select id="suggestion-intent" name="intent_id" class="form-select" required>
        @foreach($available as $intent)
          <option value="{{ $intent->id }}">{{ $intent->question }}@unless($intent->active) ({{ __('assistant.inactive') }})@endunless</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4"><button class="btn btn-primary">{{ __('assistant.add_suggestion') }}</button></div>
  </form>
@endif

@if($suggested->count() > 1)<p class="text-muted small mb-2"><i class="ri-drag-move-2-line"></i> {{ __('assistant.drag_to_reorder') }}</p>@endif
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <tbody id="suggestionSortable">
      @forelse($suggested as $intent)
        <tr data-id="{{ $intent->id }}">
          <td class="drag-handle text-muted" style="width:32px;cursor:grab"><i class="ri-draggable"></i></td>
          <td>
            {{ $intent->question }}
            @unless($intent->active)<span class="badge bg-secondary">{{ __('assistant.inactive') }}</span>@endunless
          </td>
          <td class="text-end text-nowrap">
            <a href="{{ route('admin.assistant.questions.edit', $intent->id) }}" class="btn btn-sm btn-info">{{ __('assistant.edit') }}</a>
            <form method="POST" action="{{ route('admin.assistant.suggestions.destroy', $intent->id) }}" class="d-inline">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-outline-danger">{{ __('assistant.remove') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="text-center py-4">{{ __('assistant.no_suggestions') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@include('admin.assistant._sortable', ['tbody' => 'suggestionSortable', 'url' => route('admin.assistant.suggestions.reorder')])
@endsection
