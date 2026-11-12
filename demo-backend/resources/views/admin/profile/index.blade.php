@extends('layouts.master')

@section('styles')
<style>
    #qrcode-container canvas, #qrcode-container img { display: block; margin: 0 auto; }
</style>
@endsection

@section('content')
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">My Profile</h1>
    </div>
</div>

@if(session('info_success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-1"></i>{{ session('info_success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('password_success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-1"></i>{{ session('password_success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('2fa_status') === 'confirmed')
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-shield-check-line me-1"></i>Two-factor authentication is now active.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@elseif(session('2fa_status') === 'disabled')
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="ri-shield-off-line me-1"></i>Two-factor authentication has been disabled.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@elseif(session('2fa_status') === 'codes_regenerated')
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="ri-refresh-line me-1"></i>Recovery codes have been regenerated.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    {{-- Left column --}}
    <div class="col-xl-6">

        {{-- Profile Info --}}
        <div class="card custom-card mb-3">
            <div class="card-header"><div class="card-title">Profile Information</div></div>
            <div class="card-body">
                @if($errors->updateProfileInformation->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->updateProfileInformation->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.profile.update-info') }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name"
                               value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email"
                               value="{{ old('email', $user->email) }}" required>
                        @if(! $user->hasVerifiedEmail())
                            <div class="form-text text-warning">
                                <i class="ri-error-warning-line me-1"></i>Email not verified.
                            </div>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </form>
            </div>
        </div>

        {{-- Password --}}
        <div class="card custom-card">
            <div class="card-header"><div class="card-title">Change Password</div></div>
            <div class="card-body">
                @if($errors->updatePassword->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->updatePassword->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.profile.update-password') }}">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Update Password</button>
                </form>
            </div>
        </div>

    </div>

    {{-- Right column — 2FA --}}
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Two-Factor Authentication</div>
            </div>
            <div class="card-body">

                {{-- STATE 1: Confirmed & active --}}
                @if($user->two_factor_confirmed_at)
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-success-transparent fs-12">
                            <i class="ri-shield-check-line me-1"></i>Enabled &amp; Active
                        </span>
                        <span class="text-muted fs-12">since {{ \Illuminate\Support\Carbon::parse($user->two_factor_confirmed_at)->format('M d, Y') }}</span>
                    </div>
                    <p class="text-muted fs-13 mb-4">
                        Your account is protected with an authenticator app. Use the recovery codes below if you lose access to your device.
                    </p>

                    {{-- Recovery codes --}}
                    @if($recoveryCodes)
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-semibold fs-14">Recovery Codes</span>
                            <form method="POST" action="{{ route('admin.profile.2fa.recovery-codes') }}"
                                  onsubmit="return confirm('Regenerate recovery codes? Old codes will stop working.')">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-xs">
                                    <i class="ri-refresh-line me-1"></i>Regenerate
                                </button>
                            </form>
                        </div>
                        <div class="bg-light rounded p-3">
                            <div class="row row-cols-2 g-1">
                                @foreach($recoveryCodes as $code)
                                    <div class="col">
                                        <code class="fs-13">{{ $code }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-text">Save these codes in a safe place. Each can only be used once.</div>
                    </div>
                    @endif

                    {{-- Disable 2FA --}}
                    <form method="POST" action="{{ route('admin.profile.2fa.disable') }}"
                          onsubmit="return confirm('Disable two-factor authentication? Your account will be less secure.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="ri-shield-cross-line me-1"></i>Disable Two-Factor Auth
                        </button>
                    </form>

                {{-- STATE 2: Secret set but not confirmed yet --}}
                @elseif($user->two_factor_secret)
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-warning-transparent fs-12">
                            <i class="ri-shield-line me-1"></i>Setup in Progress
                        </span>
                    </div>
                    <p class="text-muted fs-13 mb-3">
                        Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm.
                    </p>

                    {{-- QR Code --}}
                    @if($qrCodeUrl)
                        <div id="qrcode-container" class="mb-3 text-center"></div>
                        <p class="text-center text-muted fs-12 mb-3">Can't scan? Enter this key manually: <br>
                            <code id="totp-manual-key" class="fs-12"></code>
                        </p>
                    @endif

                    {{-- Confirm code form --}}
                    @if($errors->has('code'))
                        <div class="alert alert-danger py-2">{{ $errors->first('code') }}</div>
                    @endif
                    <form method="POST" action="{{ route('admin.profile.2fa.confirm') }}" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <input type="text" class="form-control" name="code"
                                   placeholder="6-digit code" maxlength="6" autocomplete="one-time-code"
                                   inputmode="numeric" pattern="\d{6}" required>
                            <button type="submit" class="btn btn-success">
                                <i class="ri-shield-check-line me-1"></i>Confirm
                            </button>
                        </div>
                    </form>

                    {{-- Cancel setup --}}
                    <form method="POST" action="{{ route('admin.profile.2fa.disable') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm text-muted p-0">
                            Cancel setup
                        </button>
                    </form>

                {{-- STATE 3: Not enabled --}}
                @else
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-secondary-transparent fs-12">
                            <i class="ri-shield-off-line me-1"></i>Disabled
                        </span>
                    </div>
                    <p class="text-muted fs-13 mb-4">
                        Add an extra layer of security to your account. When enabled, you will be asked for a code from your authenticator app each time you log in.
                    </p>
                    <form method="POST" action="{{ route('admin.profile.2fa.enable') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-shield-keyhole-line me-1"></i>Enable Two-Factor Auth
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@if(isset($qrCodeUrl) && $qrCodeUrl)
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const url = @json($qrCodeUrl);
        new QRCode(document.getElementById('qrcode-container'), {
            text: url,
            width: 180,
            height: 180,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });

        // Extract secret from otpauth URL for manual entry
        const match = url.match(/secret=([^&]+)/);
        if (match) {
            const key = document.getElementById('totp-manual-key');
            if (key) key.textContent = match[1];
        }
    });
</script>
@endif
@endsection
