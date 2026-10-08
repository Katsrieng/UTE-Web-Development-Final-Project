@extends('layouts.management')

@section('title', 'Manage Event Reservations')
@section('page-label', 'Event Reservations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Event Reservations</h1>
        <p class="text-muted mb-0">Review customer requests and manage reservation statuses.</p>
    </div>

@staffroute('management.venues.index')
<a href="{{ route('management.venues.index') }}" class="btn btn-outline-primary">Manage Venues</a>
@endstaffroute

</div>

<x-list-toolbar :action="route('management.event-reservations.index')" placeholder="Customer, email, ID, venue or event type" :fields="['status'=>['label'=>'Status','all'=>'All statuses','options'=>array_combine(App\Models\EventBooking::STATUSES, array_map('ucfirst',App\Models\EventBooking::STATUSES))], 'venue_id'=>['label'=>'Venue','all'=>'All venues','options'=>$venues->pluck('name','id')->all()], 'event_date'=>['label'=>'Event date', 'type'=>'date']]" />

<div class="table-card">
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

@staffroute('management.event-reservations.show')
<a href="{{ route('management.event-reservations.show', $eventBooking) }}"
                               class="btn btn-sm btn-outline-primary mb-2">View</a>
@endstaffroute

                            @include('management.event-reservations._record-actions', ['compact'=>true])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" ><x-list-no-results module="event reservations" :clear="route('management.event-reservations.index')" /></td>
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
