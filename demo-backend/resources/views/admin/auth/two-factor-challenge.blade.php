@extends('layouts.custom-master')

@php $bodyClass = 'authentication-background'; @endphp

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h4 class="mb-4 fw-semibold">Two-Factor Authentication</h4>

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf

                <div class="mb-3">
                    <label for="code" class="form-label">Authentication Code</label>
                    <input id="code" type="text"
                        class="form-control @error('code') is-invalid @enderror"
                        name="code" autofocus autocomplete="one-time-code">

                    @error('code')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <div class="text-center my-2">or</div>

                <div class="mb-3">
                    <label for="recovery_code" class="form-label">Recovery Code</label>
                    <input id="recovery_code" type="text"
                        class="form-control @error('recovery_code') is-invalid @enderror"
                        name="recovery_code" autocomplete="one-time-code">

                    @error('recovery_code')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
        </div>
    </div>
</div>
@endsection
