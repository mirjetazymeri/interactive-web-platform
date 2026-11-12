@extends('layouts.custom-master')

@php $bodyClass = 'authentication-background'; @endphp

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @if (session('status') === 'verification-link-sent')
                <div class="alert alert-success">
                    A new verification link has been sent to your email address.
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <h4 class="fw-semibold mb-3">Verify Your Email Address</h4>
                    <p class="mb-3">Before continuing, please check your email for a verification link.</p>

                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Resend Verification Email</button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-link text-danger">Log Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
