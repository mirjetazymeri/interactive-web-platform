@extends('emails.layout')

@section('subject'){{ __('meetings.mail.subject_'.$kind, ['type' => $typeName, 'date' => $when->isoFormat('LL')]) }}@endsection

@section('body')
    <h2>{{ __('meetings.mail.heading_'.$kind) }}</h2>
    <p>{{ __('meetings.mail.greeting', ['name' => $booking->name]) }}</p>
    <p>{{ __('meetings.mail.intro_'.$kind) }}</p>

    @if($kind === 'cancelled' && $booking->cancel_reason)
        <div class="highlight">{{ $booking->cancel_reason }}</div>
    @endif

    <table class="data">
        <tr><th>{{ __('meetings.mail.meeting') }}</th><td>{{ $typeName }}</td></tr>
        <tr><th>{{ __('meetings.format') }}</th><td>{{ $booking->format->label() }}</td></tr>
        <tr>
            <th>{{ __('meetings.mail.when') }}</th>
            <td>
                {{ $when->isoFormat('dddd, LL') }}<br>
                {{ $when->isoFormat('LT') }}–{{ $until->isoFormat('LT') }} ({{ $booking->visitor_timezone }})
                @if($businessTimezone !== $booking->visitor_timezone)
                    <br><span style="color:#888; font-size:13px;">{{ __('meetings.mail.business_time', ['time' => $when->setTimezone($businessTimezone)->isoFormat('LT'), 'timezone' => $businessTimezone]) }}</span>
                @endif
            </td>
        </tr>
        @if($kind === 'updated' && ($previousWhen || $previousFormat))
            <tr>
                <th>{{ __('meetings.mail.previously') }}</th>
                <td style="color:#888;">{{ $previousFormat?->label() }} · {{ $previousWhen?->isoFormat('dddd, LL LT') }}</td>
            </tr>
        @endif
        <tr><th>{{ __('meetings.mail.reference') }}</th><td>{{ $booking->reference }}</td></tr>
    </table>

    @if($kind !== 'cancelled')
        @if($location['kind'] === 'online')
            <h3 style="font-size:15px; margin-top:20px;">{{ __('meetings.mail.online_heading') }}</h3>
            @if($location['link_ready'])
                <p><a class="btn" href="{{ $location['url'] }}">{{ __('meetings.mail.join') }}</a></p>
                <p style="font-size:13px; word-break:break-all;">{{ $location['url'] }}</p>
            @else
                <div class="highlight">{{ __('meetings.mail.link_pending') }}</div>
            @endif
        @else
            <h3 style="font-size:15px; margin-top:20px;">{{ __('meetings.mail.in_person_heading') }}</h3>
            <p>
                @if($location['name'])<strong>{{ $location['name'] }}</strong><br>@endif
                {{ $location['address'] }}
            </p>
            @if($location['maps_url'])
                <p><a class="btn" href="{{ $location['maps_url'] }}">{{ __('meetings.mail.directions') }}</a></p>
            @endif
        @endif

        @foreach($location['instructions'] as $instruction)
            <div class="highlight" style="white-space:pre-wrap;">{{ $instruction }}</div>
        @endforeach

        @if($kind !== 'requested')
            <p style="font-size:13px; color:#888;">{{ __('meetings.mail.calendar_hint') }}</p>
        @endif
    @endif

    <hr class="divider">
    <p style="font-size:13px; color:#999;">{{ __('meetings.mail.change_hint') }}</p>
@endsection
