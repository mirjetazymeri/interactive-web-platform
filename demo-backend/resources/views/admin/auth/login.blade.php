@extends('layouts.custom-master')

@php
    $bodyClass = 'authentication-background';
@endphp

@section('styles')
@endsection

@section('content')

    <div class="authentication-basic-background">
        <img src="{{ asset('build/assets/images/media/backgrounds/9.png') }}" alt="">
    </div>

    <div class="container">
        <div class="row justify-content-center align-items-center authentication authentication-basic h-100">
            <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
                <div class="card custom-card border-0 my-4">
                    <div class="card-body p-5">
                        <div class="mb-4">
                            <a href="{{ url('/') }}">
                                <img src="{{ asset('build/assets/images/brand-logos/toggle-logo.png') }}" alt="logo" class="desktop-dark">
                            </a>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-semibold">Hi, Welcome back!</h4>
                            <p class="mb-4 text-muted fw-normal">Please enter your credentials</p>
                        </div>

                        <!-- Display validation errors -->
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li class="fs-13">{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="row gy-3">
                                <div class="col-xl-12">
                                    <label for="email" class="form-label text-default">Email</label>
                                    <input type="email" name="email" id="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        placeholder="Enter Email" value="{{ old('email') }}" required autofocus>
                                </div>

                                <div class="col-xl-12 mb-2">
                                    <label for="password" class="form-label text-default d-block">Password</label>
                                    <div class="position-relative">
                                        <input type="password" name="password" id="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            placeholder="Enter Password" required>
                                        <a href="javascript:void(0);" class="show-password-button text-muted"
                                            onclick="createpassword('password', this)">
                                            <i class="ri-eye-off-line align-middle"></i>
                                        </a>
                                    </div>

                                    <div class="mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="remember"
                                                id="remember" {{ old('remember') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="remember">
                                                Remember me
                                            </label>
                                            <a href="{{ route('password.request') }}"
                                                class="float-end link-danger fw-medium fs-12">Forget password?</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid mt-3">
                                <button type="submit" class="btn btn-primary">Sign In</button>
                            </div>
                        </form>

                        <div class="text-center my-3 authentication-barrier">
                            <span class="op-4 fs-13">OR</span>
                        </div>

                        <div class="d-grid mb-3">
                            <a href="{{ route('auth.google.redirect') }}" class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill mb-3">
                                <span class="avatar avatar-xs">
                                    <img src="{{ asset('build/assets/images/media/apps/google.png') }}" alt="">
                                </span>
                                <span class="lh-1 ms-2 fs-13 text-default fw-medium">Sign in with Google</span>
                            </a>
                            <button class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill">
                                <span class="avatar avatar-xs flex-shrink-0">
                                    <img src="{{ asset('build/assets/images/media/apps/facebook.png') }}" alt="">
                                </span>
                                <span class="lh-1 ms-2 fs-13 text-default fw-medium">Sign in with Facebook</span>
                            </button>
                        </div>

                        <div class="text-center mt-3 fw-medium">
                            Don’t have an account? <a href="{{ route('register') }}" class="text-primary">Register Here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script src="{{ asset('build/assets/show-password.js') }}"></script>
@endsection
