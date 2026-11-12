@extends('layouts.master')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h4>Locales</h4>
        <a href="{{ route('admin.locales.create') }}" class="btn btn-primary">Add Locale</a>
    </div>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Flag</th>
                <th>Active</th>
                <th>Default</th>
                <th width="150"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($locales as $locale)
                <tr>
                    <td>{{ $locale->code }}</td>
                    <td>{{ $locale->name }}</td>
                    <td>{{ $locale->flag }}</td>
                    <td>{{ $locale->is_active ? 'Yes' : 'No' }}</td>
                    <td>{{ $locale->is_default ? 'Yes' : 'No' }}</td>
                    <td>
                        <a href="{{ route('admin.locales.edit', $locale) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.locales.destroy', $locale) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('Delete?')" class="btn btn-sm btn-danger">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
