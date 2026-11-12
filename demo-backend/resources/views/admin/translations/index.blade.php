@extends('layouts.master')

@section('content')
<div class="container py-3">
    <h1 class="mb-3">Manage Translations</h1>

    {{-- Search --}}
    <div class="mb-3">
        <input type="text" id="translationSearch" class="form-control" placeholder="Search translations...">
    </div>

    {{-- Add New Translation --}}
    <form action="{{ route('admin.translations.store') }}" method="POST" class="mb-4">
        @csrf
        <div class="row g-2">
            <div class="col-12 col-md">
                <input type="text" name="key" class="form-control" placeholder="Translation key" required>
            </div>
            @foreach($locales as $locale)
                <div class="col-12 col-md">
                    <input type="text" name="translations[{{ $locale }}]"
                           class="form-control"
                           placeholder="{{ strtoupper($locale) }} value">
                </div>
            @endforeach
            <div class="col-auto">
                <button type="submit" class="btn btn-primary w-100">Add</button>
            </div>
        </div>
    </form>

    {{-- Desktop Table --}}
    <div class="table-responsive d-none d-sm-block">
        <table class="table table-bordered align-middle" id="translationsTable">
            <thead class="table-light">
                <tr>
                    <th>Key</th>
                    @foreach($locales as $locale)
                        <th>{{ strtoupper($locale) }}</th>
                    @endforeach
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @foreach($allKeys as $key)
                <tr>
                    <form action="{{ route('admin.translations.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="key" value="{{ $key }}">
                        <td class="fw-bold">{{ $key }}</td>
                        @foreach($locales as $locale)
                            <td>
                                <input type="text"
                                       name="translations[{{ $locale }}]"
                                       value="{{ $translations[$locale][$key] ?? '' }}"
                                       class="form-control">
                            </td>
                        @endforeach
                        <td class="d-flex gap-1">
                            <button type="submit" class="btn btn-success btn-sm">Save</button>
                            <button type="button"
                                    class="btn btn-danger btn-sm"
                                    onclick="deleteTranslation('{{ $key }}')">
                                Delete
                            </button>
                        </td>
                    </form>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile Cards --}}
    <div class="d-block d-sm-none" id="translationsCards">
        @forelse($allKeys as $key)
            <div class="border rounded p-3 mb-3 translation-card">
                <div class="fw-bold mb-2">{{ $key }}</div>

                <form action="{{ route('admin.translations.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="key" value="{{ $key }}">
                    @foreach($locales as $locale)
                        <div class="mb-2">
                            <label class="form-label small">{{ strtoupper($locale) }}</label>
                            <input type="text"
                                   class="form-control"
                                   name="translations[{{ $locale }}]"
                                   value="{{ $translations[$locale][$key] ?? '' }}">
                        </div>
                    @endforeach

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-sm flex-fill">Save</button>
                        <button type="button"
                                class="btn btn-danger btn-sm flex-fill"
                                onclick="deleteTranslation('{{ $key }}')">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        @empty
            <div class="text-center text-muted">No translations found.</div>
        @endforelse
    </div>

    {{-- Hidden delete form --}}
    <form id="deleteForm" method="POST" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@section('scripts')
<script>
function deleteTranslation(key) {
    if (confirm('Delete this translation?')) {
        const form = document.getElementById('deleteForm');
        form.action = "{{ route('admin.translations.destroy', ':key') }}".replace(':key', key);
        form.submit();
    }
}

document.getElementById('translationSearch').addEventListener('keyup', function () {
    let searchTerm = this.value.toLowerCase();

    // Table filter
    document.querySelectorAll('#translationsTable tbody tr').forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(searchTerm) ? '' : 'none';
    });

    // Card filter
    document.querySelectorAll('#translationsCards .translation-card').forEach(function (card) {
        card.style.display = card.innerText.toLowerCase().includes(searchTerm) ? '' : 'none';
    });
});
</script>
@endsection
