@extends('layouts.master')
@section('content')
@include('admin.assistant._header', ['title' => __('assistant.unanswered')])

<p class="text-muted mb-1">{{ __('assistant.unanswered_intro') }}</p>
<p class="text-muted small">{{ __('assistant.retention_note', ['days' => $retention]) }} {{ __('assistant.log_unanswered_help') }}</p>

<form method="GET" action="{{ route('admin.assistant.unanswered.index') }}" class="row g-2 align-items-end mb-3">
  <div class="col-md-3">
    <label class="form-label small mb-1" for="unanswered-status">{{ __('assistant.status') }}</label>
    <select id="unanswered-status" name="status" class="form-select">
      <option value="open" @selected($filters['status'] === 'open')>{{ __('assistant.status_new') }} + {{ __('assistant.status_reviewed') }}</option>
      @foreach(\App\Models\AssistantUnansweredQuestion::STATUSES as $status)
        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ __('assistant.status_'.$status) }}</option>
      @endforeach
      <option value="all" @selected($filters['status'] === 'all')>{{ __('assistant.all') }}</option>
    </select>
  </div>
  <div class="col-md-3">
    <label class="form-label small mb-1" for="unanswered-locale">{{ __('assistant.language') }}</label>
    <select id="unanswered-locale" name="locale" class="form-select">
      <option value="">{{ __('assistant.all_languages') }}</option>
      @foreach($locales as $locale)
        <option value="{{ $locale }}" @selected($filters['locale'] === $locale)>{{ config("locales.$locale.name") ?? strtoupper($locale) }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-2"><button class="btn btn-primary">{{ __('assistant.filter') }}</button></div>
</form>

<div class="card">
  <div class="card-body p-0">
    <table class="table table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>{{ __('assistant.question') }}</th>
          <th>{{ __('assistant.language') }}</th>
          <th>{{ __('assistant.occurrences') }}</th>
          <th>{{ __('assistant.last_seen') }}</th>
          <th>{{ __('assistant.status') }}</th>
          <th class="text-end">{{ __('assistant.actions') }}</th>
        </tr>
      </thead>
      <tbody>
      @forelse($questions as $question)
        <tr>
          <td style="max-width:360px">
            <div class="text-break">{{ $question->question }}</div>
            @if($question->resolvedIntent)
              <div class="small"><a href="{{ route('admin.assistant.questions.edit', $question->resolvedIntent->id) }}">→ {{ $question->resolvedIntent->question }}</a></div>
            @endif
          </td>
          <td>{{ strtoupper($question->locale) }}</td>
          <td class="fw-semibold">{{ $question->occurrences }}</td>
          <td class="text-nowrap small">{{ $question->last_seen_at?->format('Y-m-d H:i') }}</td>
          <td>
            @php($badge = ['new' => 'bg-primary', 'reviewed' => 'bg-info', 'ignored' => 'bg-secondary', 'resolved' => 'bg-success'][$question->status] ?? 'bg-light')
            <span class="badge {{ $badge }}">{{ __('assistant.status_'.$question->status) }}</span>
          </td>
          <td class="text-end">
            <div class="d-flex flex-wrap gap-1 justify-content-end">
              <a href="{{ route('admin.assistant.questions.create', ['from' => $question->id]) }}" class="btn btn-sm btn-primary">{{ __('assistant.create_answer') }}</a>
              @if($intents->isNotEmpty())
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#attach-{{ $question->id }}" aria-expanded="false">{{ __('assistant.add_as_phrasing') }}</button>
              @endif
              @php($transitions = ['new' => ['reviewed' => 'mark_reviewed', 'ignored' => 'ignore'], 'reviewed' => ['ignored' => 'ignore', 'new' => 'reopen'], 'ignored' => ['new' => 'reopen'], 'resolved' => ['new' => 'reopen']][$question->status] ?? [])
              @foreach($transitions as $status => $label)
                <form method="POST" action="{{ route('admin.assistant.unanswered.status', $question->id) }}" class="d-inline">
                  @csrf
                  <input type="hidden" name="status" value="{{ $status }}">
                  <button class="btn btn-sm btn-light">{{ __('assistant.'.$label) }}</button>
                </form>
              @endforeach
              <form method="POST" action="{{ route('admin.assistant.unanswered.destroy', $question->id) }}" class="d-inline" onsubmit="return confirm(@js(__('assistant.confirm_delete_unanswered')))">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" aria-label="{{ __('assistant.delete') }}"><i class="ri-delete-bin-line"></i></button>
              </form>
            </div>
            @if($intents->isNotEmpty())
              <form method="POST" action="{{ route('admin.assistant.unanswered.attach', $question->id) }}" class="collapse mt-2" id="attach-{{ $question->id }}">
                @csrf
                <div class="input-group input-group-sm">
                  <select name="intent_id" class="form-select" required aria-label="{{ __('assistant.choose_question') }}">
                    @foreach($intents as $intent)
                      <option value="{{ $intent->id }}">{{ \Illuminate\Support\Str::limit($intent->question, 70) }}</option>
                    @endforeach
                  </select>
                  <button class="btn btn-primary">{{ __('assistant.add') }}</button>
                </div>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="6" class="text-center py-4">{{ __('assistant.no_unanswered') }}</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
<div class="mt-3">{{ $questions->links() }}</div>
@endsection
