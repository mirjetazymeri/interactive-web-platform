@extends('emails.layout')

@section('subject') Verify your email address @endsection

@section('body')
    <h2>Verify your email address</h2>

    <p>Hi {{ $user->name }},</p>

    <p>Please click the button below to verify your email address.</p>

    <p>
        <a class="btn" href="{{ $verificationUrl }}">Verify Email</a>
    </p>

    <div class="highlight" style="word-break:break-all;">
        <strong>Or copy this link:</strong><br>
        <a href="{{ $verificationUrl }}" style="font-size:12px; color:#1a56db;">{{ $verificationUrl }}</a>
    </div>

    <hr class="divider">
    <p style="font-size:13px; color:#999;">
        This link expires in 60 minutes.
        If you did not create an account, no action is required.
    </p>
@endsection
