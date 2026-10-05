@extends('layouts.management')

@section('title', $venue->name.' Management')
@section('page-label', 'Venues')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <a href="{{ route('management.venues.index') }}" class="text-decoration-none small">&larr; Manage venues</a>
        <h1 class="h3 mt-2 mb-1">{{ $venue->name }}</h1>
        <span class="badge text-bg-{{ $venue->is_active ? 'success' : 'secondary' }}">
            {{ $venue->is_active ? 'Active' : 'Archived' }}
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('venues.show', $venue) }}" class="btn btn-outline-secondary {{ $venue->is_active ? '' : 'disabled' }}">Public View</a>
        <a href="{{ route('management.venues.edit', $venue) }}" class="btn btn-primary">Edit Venue</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h2 class="h5">Venue details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Location</dt>
                    <dd class="col-sm-8">{{ $venue->location }}</dd>
                    <dt class="col-sm-4">Capacity</dt>
                    <dd class="col-sm-8">{{ number_format($venue->capacity) }} guests</dd>
                    <dt class="col-sm-4">Price</dt>
                    <dd class="col-sm-8">{{ $venue->price !== null ? '$'.number_format((float) $venue->price, 2) : 'Not specified' }}</dd>
                    <dt class="col-sm-4">Event types</dt>
                    <dd class="col-sm-8">
                        @foreach($venue->event_types as $eventType)
                            <span class="badge text-bg-light border">{{ ucfirst($eventType) }}</span>
                        @endforeach
                    </dd>
                    <dt class="col-sm-4">Reservations</dt>
                    <dd class="col-sm-8">{{ number_format($venue->event_bookings_count) }}</dd>
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8">{{ $venue->description ?: 'No description provided.' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <h2 class="h5">Images</h2>
                @forelse($venue->images ?? [] as $image)
                    <img src="{{ asset('storage/'.$image) }}" class="img-fluid rounded mb-2 object-fit-cover w-100"
                         style="height: 150px;" alt="{{ $venue->name }} image">
                @empty
                    <p class="text-muted mb-0">No venue images uploaded.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
        <h2 class="h5 mb-0">Recent reservations</h2>
        <a href="{{ route('management.event-reservations.index', ['venue_id' => $venue->id]) }}" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Customer</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Guests</th>
                    <th>Status</th>
                    <th class="text-end pe-3">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($venue->eventBookings as $eventBooking)
                    <tr>
                        <td class="ps-3">{{ $eventBooking->user?->name ?? 'Deleted user' }}</td>
                        <td>{{ ucfirst($eventBooking->event_type) }}</td>
                        <td>{{ $eventBooking->starts_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $eventBooking->guest_count }}</td>
                        <td>{{ ucfirst($eventBooking->status) }}</td>
                        <td class="text-end pe-3">
                            <a href="{{ route('management.event-reservations.show', $eventBooking) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No reservations for this venue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
