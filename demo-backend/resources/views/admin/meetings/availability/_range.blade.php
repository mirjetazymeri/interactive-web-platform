<div class="input-group input-group-sm w-auto" data-hours-range>
  <input type="time" class="form-control" name="hours[{{ $weekday }}][{{ $index }}][start]" value="{{ $range['start'] ?? '' }}" required aria-label="{{ __('meetings.from') }}">
  <span class="input-group-text">–</span>
  <input type="time" class="form-control" name="hours[{{ $weekday }}][{{ $index }}][end]" value="{{ $range['end'] ?? '' }}" required aria-label="{{ __('meetings.to') }}">
  <button type="button" class="btn btn-outline-danger" data-hours-remove aria-label="{{ __('meetings.remove') }}"><i class="ri-close-line"></i></button>
</div>
