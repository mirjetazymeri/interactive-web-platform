@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Edit User: {{ $user->name }}</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.edit', $user->id) }}">
    @csrf
    <div class="row">
        <!-- Basic Info -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">User Details</div></div>
                <div class="card-body">
                    <div class="form-group mb-2">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-2">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-2">
                        <label>New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                        <input type="password" class="form-control" name="password">
                        @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-2">
                        <label>Confirm New Password</label>
                        <input type="password" class="form-control" name="password_confirmation">
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">Assigned Roles</div></div>
                <div class="card-body">
                    @foreach($roles as $role)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->id }}"
                                   id="role-{{ $role->id }}"
                                   {{ in_array($role->id, old('roles', $assignedRoles)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="role-{{ $role->id }}">
                                {{ $role->name }}
                            </label>
                        </div>
                    @endforeach
                    @error('roles') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        <!-- Access Levels -->
<div class="card custom-card mt-3">
    <div class="card-header"><div class="card-title">Assigned Access Levels</div></div>
    <div class="card-body">
        @foreach($accessLevels as $level)
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="access_levels[]" value="{{ $level->id }}"
                       id="access-level-{{ $level->id }}"
                       {{ in_array($level->id, old('access_levels', $assignedAccessLevels)) ? 'checked' : '' }}>
                <label class="form-check-label" for="access-level-{{ $level->id }}">
                    {{ $level->name }}
                </label>
            </div>
        @endforeach
        @error('access_levels') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
</div>

<div class="card custom-card mt-3">
    <div class="card-header">
        <div class="card-title">Assigned Channels</div>
    </div>
    <div class="card-body">
        @foreach($channels as $channel)
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="channels[]"
                       value="{{ $channel->id }}"
                       id="channel-{{ $channel->id }}"
                       {{ in_array($channel->id, old('channels', $assignedChannels)) ? 'checked' : '' }}>
                <label class="form-check-label" for="channel-{{ $channel->id }}">
                    {{ $channel->name }}
                </label>
            </div>
        @endforeach
    </div>
</div>


        <div class="col-12 text-end mt-3">
            <button type="submit" class="btn btn-success">Update User</button>
        </div>
    </div>
</form>

<div class="card custom-card mt-3">
    <div class="card-header">
        <div class="card-title">Two-Factor Authentication</div>
    </div>
    <div class="card-body d-flex align-items-center justify-content-between gap-3">
        @if($user->two_factor_confirmed_at)
            <div>
                <span class="badge bg-success-transparent fs-12"><i class="ri-shield-check-line me-1"></i>Enabled &amp; Confirmed</span>
                <p class="text-muted mb-0 mt-1 fs-12">Confirmed on {{ $user->two_factor_confirmed_at->format('M d, Y H:i') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.users.disable-2fa', $user->id) }}"
                  onsubmit="return confirm('Disable 2FA for {{ addslashes($user->name) }}? They will need to re-enable it themselves.')">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm">
                    <i class="ri-shield-cross-line me-1"></i>Disable 2FA
                </button>
            </form>
        @elseif($user->two_factor_secret)
            <div>
                <span class="badge bg-warning-transparent fs-12"><i class="ri-shield-line me-1"></i>Enabled — Not Confirmed</span>
                <p class="text-muted mb-0 mt-1 fs-12">User started setup but hasn't confirmed yet.</p>
            </div>
            <form method="POST" action="{{ route('admin.users.disable-2fa', $user->id) }}"
                  onsubmit="return confirm('Clear the pending 2FA setup for {{ addslashes($user->name) }}?')">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="ri-shield-cross-line me-1"></i>Clear 2FA Setup
                </button>
            </form>
        @else
            <div>
                <span class="badge bg-secondary-transparent fs-12"><i class="ri-shield-off-line me-1"></i>Disabled</span>
                <p class="text-muted mb-0 mt-1 fs-12">The user can enable 2FA from their profile settings.</p>
            </div>
        @endif
    </div>
</div>

@endsection
