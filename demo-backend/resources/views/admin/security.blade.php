@extends('layouts.master')
@php $bodyClass = ''; @endphp

@section('content')
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-xl-6 col-lg-7 col-md-8">
      <div class="card custom-card">
        <div class="card-header">
          <h4 class="fw-semibold mb-0">Two-Factor Authentication</h4>
        </div>

        <div class="card-body">
          {{-- Fortify status flashes --}}
          @if (session('status') === 'two-factor-authentication-disabled')
            <div class="alert alert-warning">Two-factor authentication has been disabled.</div>
          @elseif (session('status') === 'two-factor-authentication-enabled')
            <div class="alert alert-success">Two-factor authentication has been enabled. Please confirm with a code.</div>
          @elseif (session('status') === 'recovery-codes-generated')
            <div class="alert alert-info">New recovery codes have been generated.</div>
          @endif

          {{-- Validation errors --}}
          @if ($errors->any())
            <div class="alert alert-danger">
              {{ $errors->first() }}
            </div>
          @endif

          {{-- 1) Not enabled --}}
          @if (! auth()->user()->two_factor_secret)
            <p class="mb-4">Two-factor authentication is currently <strong>disabled</strong>.</p>

            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
              @csrf
              <button type="submit" class="btn btn-primary">Enable Two-Factor Authentication</button>
            </form>

          {{-- 2) Enabled but NOT confirmed --}}
          @elseif (is_null(auth()->user()->two_factor_confirmed_at))
            <p class="mb-3">
              Two-factor authentication is <strong>pending confirmation</strong>.
              Scan the QR code below with your authenticator app and enter the 6-digit code to confirm.
            </p>

            <div class="mb-4">{!! auth()->user()->twoFactorQrCodeSvg() !!}</div>

            <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="mb-4">
              @csrf
              <div class="mb-3">
                <label for="code" class="form-label">Authentication Code</label>
                <input id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
                       class="form-control" placeholder="123456" required>
              </div>
              <button type="submit" class="btn btn-success">Confirm & Activate</button>
            </form>

            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger btn-sm">Cancel & Disable</button>
            </form>

          {{-- 3) Enabled & confirmed --}}
          @else
            <p class="mb-2">Two-factor authentication is <strong>enabled</strong> ✅</p>
            <div class="mb-3">
              <span class="badge bg-success">Confirmed at: {{ auth()->user()->two_factor_confirmed_at }}</span>
            </div>

            <div class="mb-4">
              <p class="mb-2"><strong>Recovery Codes</strong></p>
              <div class="alert alert-light">
                <ul class="list-unstyled mb-0">
                  @foreach (json_decode(decrypt(auth()->user()->two_factor_recovery_codes), true) as $code)
                    <li class="mb-1 font-monospace">{{ $code }}</li>
                  @endforeach
                </ul>
              </div>
              <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm">Regenerate Recovery Codes</button>
              </form>
            </div>

            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger">Disable Two-Factor Authentication</button>
            </form>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
