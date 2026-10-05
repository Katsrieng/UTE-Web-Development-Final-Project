@extends('layouts.app')

@section('title', 'Manage Event Reservations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Event Reservations</h1>
        <p class="text-muted mb-0">Review customer requests and manage reservation statuses.</p>
    </div>
    <a href="{{ route('management.venues.index') }}" class="btn btn-outline-primary">Manage Venues</a>
</div>

<form method="GET" action="{{ route('management.event-reservations.index') }}" class="card card-body mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label for="venue_id" class="form-label">Venue</label>
            <select id="venue_id" name="venue_id" class="form-select">
                <option value="">All venues</option>
                @foreach($venues as $venue)
                    <option value="{{ $venue->id }}" @selected((int) request('venue_id') === $venue->id)>{{ $venue->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="event_date" class="form-label">Event date</label>
            <input id="event_date" type="date" name="event_date" value="{{ request('event_date') }}" class="form-control">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-secondary flex-grow-1">Filter</button>
            <a href="{{ route('management.event-reservations.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Reference</th>
                    <th>Customer</th>
                    <th>Venue</th>
                    <th>Event</th>
                    <th>Date &amp; time</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($eventBookings as $eventBooking)
                    @php
                        $statusColor = match($eventBooking->status) {
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'cancelled' => 'secondary',
                            default => 'warning',
                        };
                    @endphp
                    <tr>
                        <td class="ps-4">#{{ $eventBooking->id }}</td>
                        <td>{{ $eventBooking->user?->name ?? 'Deleted user' }}</td>
                        <td>{{ $eventBooking->venue->name }}</td>
                        <td>{{ ucfirst($eventBooking->event_type) }}<br><span class="text-muted small">{{ $eventBooking->guest_count }} guests</span></td>
                        <td>
                            {{ $eventBooking->starts_at->format('M j, Y') }}<br>
                            <span class="text-muted small">{{ $eventBooking->starts_at->format('g:i A') }}–{{ $eventBooking->ends_at->format('g:i A') }}</span>
                        </td>
                        <td><span class="badge text-bg-{{ $statusColor }}">{{ ucfirst($eventBooking->status) }}</span></td>
                        <td class="text-end pe-4">
                            <a href="{{ route('management.event-reservations.show', $eventBooking) }}"
                               class="btn btn-sm btn-outline-primary">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No event reservations match the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($eventBookings->hasPages())
    <div class="mt-4">
        {{ $eventBookings->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
