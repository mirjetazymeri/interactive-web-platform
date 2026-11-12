@extends('emails.layout')

@section('subject') [Contact] {{ $formSubject }} @endsection

@section('body')
    <h2>New Contact Form Submission</h2>

    <table class="data">
        <tr><th>From</th><td>{{ $senderName }}</td></tr>
        <tr><th>Email</th><td><a href="mailto:{{ $senderEmail }}">{{ $senderEmail }}</a></td></tr>
        <tr><th>Subject</th><td>{{ $formSubject }}</td></tr>
    </table>

    <h3 style="font-size:15px; margin-top:20px;">Message</h3>
    <div class="highlight" style="white-space:pre-wrap; font-size:14px; line-height:1.65;">{{ $formMessage }}</div>

    <p style="margin-top:20px;">
        Reply directly to this email to respond to the sender.
    </p>
@endsection
