<span class="badge {{ $format === \App\Enums\MeetingFormat::Online ? 'bg-info-transparent text-info' : 'bg-warning-transparent text-warning' }}">
  <i class="{{ $format === \App\Enums\MeetingFormat::Online ? 'ri-video-chat-line' : 'ri-building-line' }} me-1"></i>{{ $format->label() }}
</span>
