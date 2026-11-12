@php
    // $employee is passed in from create/edit
@endphp

@if ($errors->any())
    <div class="alert alert-danger mb-3">
        <ul class="mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">First name</label>
        <input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" class="form-control"
            required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Last name</label>
        <input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" class="form-control"
            required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Position</label>
        <input type="text" name="position" value="{{ old('position', $employee->position) }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label">Department</label>
        <input type="text" name="department" value="{{ old('department', $employee->department) }}"
            class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Hired at</label>
        <input type="date" name="hired_at"
            value="{{ old('hired_at', optional($employee->hired_at)->format('Y-m-d')) }}" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Salary</label>
        <input type="number" step="0.01" name="salary" value="{{ old('salary', $employee->salary) }}"
            class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach (['active', 'inactive', 'terminated'] as $opt)
                <option value="{{ $opt }}" @selected(old('status', $employee->status ?? 'active') === $opt)>{{ ucfirst($opt) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Avatar</label>
        <input type="file" name="avatar" class="form-control" accept="image/*">
        @if ($employee->avatar_path)
            <img src="{{ asset('storage/' . $employee->avatar_path) }}" alt="Avatar"
                style="height:80px;width:80px;object-fit:cover;border-radius:50%">
        @endif

    </div>
</div>
