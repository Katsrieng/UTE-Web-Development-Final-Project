@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Packages</h2>
        <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">+ New Package</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Price</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($packages as $package)
                <tr>
                    <td>{{ $package->name }}</td>
                    <td>{{ ucfirst($package->type) }}</td>
                    <td>${{ number_format($package->price, 2) }}</td>
                    <td>
                        <span class="badge {{ $package->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($package->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this package?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No packages yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $packages->links() }}
</div>
@endsection
