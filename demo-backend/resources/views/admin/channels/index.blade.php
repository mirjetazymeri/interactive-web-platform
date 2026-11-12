@extends('layouts.master')

@section('content')

<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Channels</h1>
        <a href="{{ route('admin.channels.create') }}" class="btn btn-primary">Add Channel</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card custom-card">
    <div class="card-header">
        <div class="card-title">All Channels</div>
    </div>

    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Domain</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($channels as $channel)
                    <tr>
                        <td>{{ $channel->name }}</td>
                        <td>{{ $channel->code }}</td>
                        <td>{{ $channel->domain ?? '—' }}</td>
                        <td>
                            <a href="{{ route('admin.channels.edit', $channel->id) }}" class="btn btn-sm btn-info">Edit</a>

                            <a href="{{ route('admin.channels.delete', $channel->id) }}"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Delete this channel?')">
                                Delete
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No channels found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
