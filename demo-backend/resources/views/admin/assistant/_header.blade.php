{{-- Page title with the current channel, and an optional action button. --}}
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h1 class="fs-18 mb-1">{{ $title }}</h1>
    <div class="text-muted small">{{ __('assistant.current_channel') }}: {{ auth()->user()->currentChannel?->name ?? 'N/A' }}</div>
  </div>
  @isset($action)
    <a href="{{ $action['href'] }}" class="btn {{ $action['class'] ?? 'btn-primary' }}">{{ $action['label'] }}</a>
  @endisset
</div>
@include('admin.assistant._alerts')
