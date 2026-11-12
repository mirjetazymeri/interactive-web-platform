@extends('emails.layout')

@section('subject') [Admin] New User Registration: {{ $user->name }} @endsection

@section('body')
    <h2>New User Registered</h2>

    <p>A new customer has created an account.</p>

    <table class="data">
        <tr><th>Name</th><td>{{ $user->name }}</td></tr>
        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
        <tr><th>Registered</th><td>{{ $user->created_at?->format('d M Y, H:i') }}</td></tr>
        <tr>
            <th>Email Verified</th>
            <td>
                @if($user->email_verified_at)
                    <span class="badge">Yes — {{ $user->email_verified_at->format('d M Y, H:i') }}</span>
                @else
                    <span class="badge" style="background:#fff0f0; color:#e53e3e;">No</span>
                @endif
            </td>
        </tr>
    </table>
@endsection
