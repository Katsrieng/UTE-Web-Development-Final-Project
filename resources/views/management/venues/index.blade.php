@extends('layouts.management')

@section('title', 'Manage Venues')
@section('page-label', 'Venues')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Manage Venues</h1>
        <p class="text-muted mb-0">Create, update, or archive event spaces.</p>
    </div>
    <a href="{{ route('management.venues.create') }}" class="btn btn-primary">Add Venue</a>
</div>

<form method="GET" action="{{ route('management.venues.index') }}" class="card card-body mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-lg-4">
            <label for="search" class="form-label">Search</label>
            <input id="search" type="search" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Name or location">
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="event_type" class="form-label">Event type</label>
            <select id="event_type" name="event_type" class="form-select">
                <option value="">All types</option>
                @foreach($eventTypes as $eventType)
                    <option value="{{ $eventType }}" @selected(request('event_type') === $eventType)>
                        {{ ucfirst($eventType) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-secondary flex-grow-1">Filter</button>
            <a href="{{ route('management.venues.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Venue</th>
                    <th>Capacity</th>
                    <th>Event types</th>
                    <th>Reservations</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venues as $venue)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-semibold d-block">{{ $venue->name }}</span>
                            <span class="text-muted small">{{ $venue->location }}</span>
                        </td>
                        <td>{{ number_format($venue->capacity) }}</td>
                        <td>
                            @foreach($venue->event_types as $eventType)
                                <span class="badge text-bg-light border">{{ ucfirst($eventType) }}</span>
                            @endforeach
                        </td>
                        <td>{{ number_format($venue->event_bookings_count) }}</td>
                        <td>
                            <span class="badge text-bg-{{ $venue->is_active ? 'success' : 'secondary' }}">
                                {{ $venue->is_active ? 'Active' : 'Archived' }}
                            </span>
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            <a href="{{ route('management.venues.show', $venue) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('management.venues.edit', $venue) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            @if($venue->is_active)
                                <form method="POST" action="{{ route('management.venues.destroy', $venue) }}" class="d-inline"
                                      onsubmit="return confirm('Archive this venue? Existing reservations will be preserved.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Archive</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No venues match the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($venues->hasPages())
    <div class="mt-4">
        {{ $venues->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
