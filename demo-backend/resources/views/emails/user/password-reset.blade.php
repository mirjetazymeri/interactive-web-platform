@extends('emails.layout')

@section('subject') Reset your password @endsection

@section('body')
    <h2>Password Reset Request</h2>

    <p>Hi {{ $user->name }},</p>

    <p>You are receiving this email because we received a password reset request for your account.</p>

    <p>
        <a class="btn" href="{{ $resetUrl }}">Reset Password</a>
    </p>

    <div class="highlight" style="word-break:break-all;">
        <strong>Or copy this link:</strong><br>
        <a href="{{ $resetUrl }}" style="font-size:12px; color:#1a56db;">{{ $resetUrl }}</a>
    </div>

    <hr class="divider">
    <p style="font-size:13px; color:#999;">
        This link expires in 60 minutes.
        If you did not request a password reset, no further action is required.
    </p>
@endsection
