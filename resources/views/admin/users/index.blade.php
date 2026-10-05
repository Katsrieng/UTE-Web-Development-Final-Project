@extends('layouts.management')

@section('title', 'Manage Users')
@section('page-label', 'Users')

@section('content')

<div class="page-heading">
    <div>
        <p class="section-kicker">Access control</p>
        <h1>Users</h1>
        <p>Manage customer, staff, and administrator accounts.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-hotel"><i class="bi bi-person-plus me-1"></i> Add User</a>
</div>

{{-- Search + filter --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="filter-panel row g-2 mb-4">
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

<div class="table-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
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
                            <td class="ps-4">#{{ $user->id }}</td>
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
                            <td><x-status-badge :status="$user->is_active ? 'active' : 'disabled'" /></td>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>

                                @unless($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          class="d-inline"
                                          >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete {{ $user->name }}? This cannot be undone."><i class="bi bi-trash"></i></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"><x-empty-state icon="bi-people" title="No users found" message="Try different search filters or add a user account." /></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links('pagination::bootstrap-5') }}
    </div>
</div>

@endsection
