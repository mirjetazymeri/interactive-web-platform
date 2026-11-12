@php($class = ['confirmed' => 'bg-success', 'pending_approval' => 'bg-warning', 'cancelled' => 'bg-secondary', 'completed' => 'bg-primary', 'no_show' => 'bg-danger'][$status] ?? 'bg-light text-body')
<span class="badge {{ $class }}">{{ __('meetings.status_'.$status) }}</span>
