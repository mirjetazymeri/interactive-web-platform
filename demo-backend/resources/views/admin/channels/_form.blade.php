
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
        <label class="form-label">Name</label>
        <input type="text"
               name="name"
               value="{{ old('name', $channel->name ?? '') }}"
               class="form-control"
               required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Code</label>
        <input type="text"
               name="code"
               value="{{ old('code', $channel->code ?? '') }}"
               class="form-control"
               required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Domain</label>
        <input type="text"
               name="domain"
               value="{{ old('domain', $channel->domain ?? '') }}"
               class="form-control">
    </div>

</div>
