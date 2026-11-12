@extends('layouts.custom-master')

@php $bodyClass = 'authentication-background'; @endphp

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h4 class="mb-4 fw-semibold">Reset Password</h4>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf

                <input type="hidden" name="token" value="{{ request()->route('token') }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input id="email" type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email', request('email')) }}" required autofocus>

                    @error('email')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input id="password" type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        name="password" required>

                    @error('password')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password-confirm" class="form-label">Confirm New Password</label>
                    <input id="password-confirm" type="password" class="form-control"
                        name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
        </div>
    </div>
</div>
@endsection
