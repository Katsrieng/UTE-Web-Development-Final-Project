@extends('layouts.app')

@section('title', 'My Event Reservations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">My Event Reservations</h1>
        <p class="text-muted mb-0">Track your requests and their current status.</p>
    </div>
    <a href="{{ route('event-reservations.create') }}" class="btn btn-primary">New Reservation</a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Venue</th>
                    <th>Event</th>
                    <th>Date &amp; time</th>
                    <th>Guests</th>
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
                        <td class="ps-4 fw-semibold">{{ $eventBooking->venue->name }}</td>
                        <td>{{ ucfirst($eventBooking->event_type) }}</td>
                        <td>
                            {{ $eventBooking->starts_at->format('M j, Y') }}<br>
                            <span class="text-muted small">
                                {{ $eventBooking->starts_at->format('g:i A') }}–{{ $eventBooking->ends_at->format('g:i A') }}
                            </span>
                        </td>
                        <td>{{ number_format($eventBooking->guest_count) }}</td>
                        <td><span class="badge text-bg-{{ $statusColor }}">{{ ucfirst($eventBooking->status) }}</span></td>
                        <td class="text-end pe-4">
                            <a href="{{ route('event-reservations.show', $eventBooking) }}"
                               class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <h2 class="h5">No event reservations yet</h2>
                            <p class="text-muted">Browse venues and submit your first request.</p>
                            <a href="{{ route('venues.index') }}" class="btn btn-primary">Browse Venues</a>
                        </td>
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
