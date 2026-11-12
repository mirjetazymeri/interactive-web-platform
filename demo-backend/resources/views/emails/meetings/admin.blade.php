@extends('emails.layout')

@section('subject'){{ __('meetings.mail.admin_subject_'.$kind, ['name' => $booking->name, 'date' => $when->isoFormat('LLL')]) }}@endsection

@section('body')
    <h2>{{ __('meetings.mail.admin_heading_'.$kind) }}</h2>

    @if($needsLink)
        <div class="highlight"><strong>{{ __('meetings.mail.admin_link_needed') }}</strong></div>
    @endif

    <table class="data">
        <tr><th>{{ __('meetings.mail.meeting') }}</th><td>{{ $typeName }}</td></tr>
        <tr><th>{{ __('meetings.format') }}</th><td><span class="badge">{{ $booking->format->label() }}</span></td></tr>
        <tr><th>{{ __('meetings.mail.when') }}</th><td>{{ $when->isoFormat('dddd, LL') }}<br>{{ $when->isoFormat('LT') }}–{{ $until->isoFormat('LT') }} ({{ $timezone }})</td></tr>
        @if($location['kind'] === 'in_person')
            <tr><th>{{ __('meetings.office') }}</th><td>{{ $location['name'] }}<br>{{ $location['address'] }}</td></tr>
        @else
            <tr><th>{{ __('meetings.mail.video_link') }}</th><td>{{ $location['link_ready'] ? $location['url'] : __('meetings.link_status_pending') }}</td></tr>
        @endif
        <tr><th>{{ __('meetings.name') }}</th><td>{{ $booking->name }}</td></tr>
        <tr><th>{{ __('meetings.email') }}</th><td><a href="mailto:{{ $booking->email }}">{{ $booking->email }}</a></td></tr>
        @if($booking->phone)<tr><th>{{ __('meetings.phone') }}</th><td>{{ $booking->phone }}</td></tr>@endif
        @if($booking->company)<tr><th>{{ __('meetings.company') }}</th><td>{{ $booking->company }}</td></tr>@endif
        <tr><th>{{ __('meetings.status') }}</th><td>{{ __('meetings.status_'.$booking->status) }}</td></tr>
        <tr><th>{{ __('meetings.mail.reference') }}</th><td>{{ $booking->reference }}</td></tr>
    </table>

    @if($booking->project_details)
        <h3 style="font-size:15px; margin-top:20px;">{{ __('meetings.project_details') }}</h3>
        <div class="highlight" style="white-space:pre-wrap; font-size:14px;">{{ $booking->project_details }}</div>
    @endif

    <p><a class="btn" href="{{ $url }}">{{ __('meetings.mail.admin_open') }}</a></p>
@endsection
