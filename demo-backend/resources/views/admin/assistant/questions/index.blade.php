@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.questions'), 'action' => ['href' => route('admin.assistant.questions.create'), 'label' => __('assistant.add_question')]])

<form method="GET" action="{{ route('admin.assistant.questions.index') }}" class="row g-2 align-items-end mb-3">
  <div class="col-md-5">
    <label class="form-label small mb-1" for="question-search">{{ __('assistant.search') }}</label>
    <input id="question-search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="{{ __('assistant.search_placeholder') }}" maxlength="100">
  </div>
  <div class="col-md-3">
    <label class="form-label small mb-1" for="question-category">{{ __('assistant.category') }}</label>
    <select id="question-category" name="category" class="form-select">
      <option value="">{{ __('assistant.all') }}</option>
      <option value="none" @selected($filters['category'] === 'none')>{{ __('assistant.no_category') }}</option>
      @foreach($categories as $category)
        <option value="{{ $category->id }}" @selected($filters['category'] === (string) $category->id)>{{ $category->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label small mb-1" for="question-status">{{ __('assistant.status') }}</label>
    <select id="question-status" name="status" class="form-select">
      <option value="">{{ __('assistant.all') }}</option>
      <option value="active" @selected($filters['status'] === 'active')>{{ __('assistant.active') }}</option>
      <option value="inactive" @selected($filters['status'] === 'inactive')>{{ __('assistant.inactive') }}</option>
    </select>
  </div>
  <div class="col-md-2 d-flex gap-2">
    <button class="btn btn-primary">{{ __('assistant.filter') }}</button>
    @if($filtered)<a href="{{ route('admin.assistant.questions.index') }}" class="btn btn-light">{{ __('assistant.reset') }}</a>@endif
  </div>
</form>

@unless($filtered)
  @if($questions->count() > 1)<p class="text-muted small mb-2"><i class="ri-drag-move-2-line"></i> {{ __('assistant.drag_to_reorder') }}</p>@endif
@endunless

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          @unless($filtered)<th style="width:32px"></th>@endunless
          <th>{{ __('assistant.question') }}</th>
          <th>{{ __('assistant.category') }}</th>
          <th>{{ __('assistant.related_content') }}</th>
          <th>{{ __('assistant.status') }}</th>
          <th class="text-end">{{ __('assistant.actions') }}</th>
        </tr>
      </thead>
      <tbody id="questionSortable">
      @forelse($questions as $intent)
        <tr data-id="{{ $intent->id }}">
          @unless($filtered)<td class="drag-handle text-muted" style="cursor:grab"><i class="ri-draggable"></i></td>@endunless
          <td>
            <div class="fw-semibold">{{ $intent->question }}</div>
            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($intent->answer, 110) }}</div>
          </td>
          <td>{{ $intent->category?->name ?? '—' }}</td>
          <td>
            @if($intent->source_type)
              <span class="badge bg-info-transparent text-info">{{ __('assistant.source_'.(in_array($intent->source_type, ['contact', 'services', 'projects', 'company'], true) ? $intent->source_type : ($intent->source_type === 'project' ? 'group_projects' : 'group_services'))) }}</span>
            @endif
            @if($intent->page_id)<span class="badge bg-light text-dark"><i class="ri-links-line"></i></span>@endif
          </td>
          <td>
            @if($intent->active)
              <span class="badge bg-success">{{ __('assistant.active') }}</span>
            @else
              <span class="badge bg-secondary">{{ __('assistant.inactive') }}</span>
            @endif
            @if($intent->is_suggested)<span class="badge bg-primary-transparent text-primary">{{ __('assistant.suggested_badge') }}</span>@endif
          </td>
          <td class="text-end text-nowrap">
            <a href="{{ route('admin.assistant.questions.edit', $intent->id) }}" class="btn btn-sm btn-info">{{ __('assistant.edit') }}</a>
            <form method="POST" action="{{ route('admin.assistant.questions.destroy', $intent->id) }}" class="d-inline" onsubmit="return confirm(@js(__('assistant.confirm_delete_question')))">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">{{ __('assistant.delete') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center py-4">{{ __('assistant.no_questions') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

@unless($filtered)
  @include('admin.assistant._sortable', ['tbody' => 'questionSortable', 'url' => route('admin.assistant.questions.reorder')])
@endunless
@endsection
