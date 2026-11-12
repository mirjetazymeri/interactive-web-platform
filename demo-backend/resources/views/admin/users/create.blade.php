@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Create User</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.create') }}">
    @csrf
    <div class="row">
        <!-- Basic Info -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">User Details</div></div>
                <div class="card-body">
                    <div class="form-group mb-2">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-2">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email') }}" required>
                        @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-2">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" required>
                        @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group mb-2">
                        <label>Confirm Password</label>
                        <input type="password" class="form-control" name="password_confirmation" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header"><div class="card-title">Assign Roles</div></div>
                <div class="card-body">
                    @foreach($roles as $role)
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->id }}"
                                   id="role-{{ $role->id }}"
                                   {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
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
    <div class="card-header"><div class="card-title">Assign Access Levels</div></div>
    <div class="card-body">
        @foreach($accessLevels as $level)
            <div class="form-check">
                <input type="checkbox" class="form-check-input" name="access_levels[]" value="{{ $level->id }}"
                       id="access-level-{{ $level->id }}"
                       {{ in_array($level->id, old('access_levels', [])) ? 'checked' : '' }}>
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
        <div class="card-title">Assign Channels</div>
    </div>
    <div class="card-body">
        @foreach($channels as $channel)
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="channels[]"
                       value="{{ $channel->id }}"
                       id="channel-{{ $channel->id }}"
                       {{ in_array($channel->id, old('channels', [])) ? 'checked' : '' }}>
                <label class="form-check-label" for="channel-{{ $channel->id }}">
                    {{ $channel->name }}
                </label>
            </div>
        @endforeach
    </div>
</div>


        <div class="col-12 text-end mt-3">
            <button type="submit" class="btn btn-primary">Create User</button>
        </div>
    </div>
</form>

@endsection
