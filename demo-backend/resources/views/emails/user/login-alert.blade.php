@extends('emails.layout')

@section('subject') New login to your account @endsection

@section('body')
    <h2>New sign-in detected</h2>

    <p>Hi {{ $user->name }},</p>

    <p>We detected a new login to your account. If this was you, no action is needed.</p>

    <table class="data">
        <tr><th>Time</th><td>{{ $loginAt }}</td></tr>
        <tr><th>IP Address</th><td>{{ $ip }}</td></tr>
        <tr>
            <th>Browser / Device</th>
            <td style="font-size:12px; word-break:break-all;">{{ Str::limit($userAgent, 120) }}</td>
        </tr>
    </table>

    <div class="highlight" style="border-color:#e53e3e;">
        <strong style="color:#e53e3e;">Not you?</strong>
        Please <a href="{{ config('app.url') }}/password/reset">reset your password</a> immediately
        and contact support.
    </div>
@endsection
