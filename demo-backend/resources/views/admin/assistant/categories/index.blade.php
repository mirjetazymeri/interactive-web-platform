@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.categories'), 'action' => ['href' => route('admin.assistant.categories.create'), 'label' => __('assistant.add_category')]])
<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>{{ __('assistant.category_name') }}</th>
          <th>{{ __('assistant.questions_count') }}</th>
          <th>{{ __('assistant.status') }}</th>
          <th>{{ __('assistant.order') }}</th>
          <th class="text-end">{{ __('assistant.actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($categories as $category)
        <tr>
          <td class="fw-semibold">{{ $category->name }}</td>
          <td><a href="{{ route('admin.assistant.questions.index', ['category' => $category->id]) }}">{{ $category->intents_count }}</a></td>
          <td>
            @if($category->active)
              <span class="badge bg-success">{{ __('assistant.active') }}</span>
            @else
              <span class="badge bg-secondary">{{ __('assistant.inactive') }}</span>
            @endif
          </td>
          <td>{{ $category->position }}</td>
          <td class="text-end text-nowrap">
            <a href="{{ route('admin.assistant.categories.edit', $category->id) }}" class="btn btn-sm btn-info">{{ __('assistant.edit') }}</a>
            <form method="POST" action="{{ route('admin.assistant.categories.destroy', $category->id) }}" class="d-inline" onsubmit="return confirm(@js(__('assistant.confirm_delete_category')))">
              @csrf @method('DELETE')
              <button class="btn btn-sm btn-danger">{{ __('assistant.delete') }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center py-4">{{ __('assistant.no_categories') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
