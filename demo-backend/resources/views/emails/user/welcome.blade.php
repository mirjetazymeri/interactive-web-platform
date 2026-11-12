@extends('emails.layout')

@section('subject') Welcome to {{ setting('store.company_name', config('app.name')) }}! @endsection

@section('body')
    <h2>Welcome, {{ $user->name }}!</h2>

    <p>Your account has been created successfully. You can now log in and start shopping.</p>

    <table class="data">
        <tr><th>Name</th><td>{{ $user->name }}</td></tr>
        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
        <tr><th>Registered</th><td>{{ $user->created_at?->format('d M Y, H:i') }}</td></tr>
    </table>

    <p>
        <a class="btn" href="{{ config('app.url') }}">Go to Store</a>
    </p>

    <hr class="divider">
    <p style="font-size:13px; color:#999;">
        If you did not create this account, you can safely ignore this email.
    </p>
@endsection
