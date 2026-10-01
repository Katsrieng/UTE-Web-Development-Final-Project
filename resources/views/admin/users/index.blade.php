@extends('layouts.app')

@section('title', 'Manage Users')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">Users</h2>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add User</a>
</div>

{{-- Search + filter --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 mb-3">
    <div class="col-md-5">
        <input type="text" name="search" value="{{ request('search') }}"
               class="form-control" placeholder="Search by name or email">
    </div>
    <div class="col-md-3">
        <select name="role" class="form-select">
            <option value="">All roles</option>
            @foreach(\App\Models\User::ROLES as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                {{ $user->name }}
                                @if($user->is(auth()->user()))
                                    <span class="badge text-bg-info">You</span>
                                @endif
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>
                                @php
                                    $color = match($user->role) {
                                        'admin' => 'danger',
                                        'staff' => 'primary',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $color }}">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-warning">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-warning">Edit</a>

                                @unless($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection
