@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Membership Types</h2>
        <a href="{{ route('admin.membership-types.create') }}" class="btn btn-primary">+ New Membership Type</a>
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
                <th>Discount</th>
                <th>Duration</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($membershipTypes as $type)
                <tr>
                    <td>{{ $type->name }}</td>
                    <td>{{ $type->discount_percentage }}%</td>
                    <td>{{ $type->duration_months }} months</td>
                    <td>
                        <span class="badge {{ $type->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($type->status) }}
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.membership-types.edit', $type) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form action="{{ route('admin.membership-types.destroy', $type) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this membership type?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No membership types yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $membershipTypes->links() }}
</div>
@endsection
