@extends('layouts.app')

@section('title', 'Event Reservation #'.$eventBooking->id)

@section('content')
@php
    $statusColor = match($eventBooking->status) {
        'approved' => 'success',
        'rejected' => 'danger',
        'cancelled' => 'secondary',
        default => 'warning',
    };
@endphp

<div class="mb-3">
    <a href="{{ route('event-reservations.index') }}" class="text-decoration-none">&larr; My reservations</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center p-4">
                <div>
                    <p class="text-muted small mb-1">Reservation #{{ $eventBooking->id }}</p>
                    <h1 class="h3 mb-0">{{ $eventBooking->venue->name }}</h1>
                </div>
                <span class="badge text-bg-{{ $statusColor }} fs-6">{{ ucfirst($eventBooking->status) }}</span>
            </div>

            <div class="card-body p-4">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Event type</dt>
                    <dd class="col-sm-8">{{ ucfirst($eventBooking->event_type) }}</dd>

                    <dt class="col-sm-4">Date</dt>
                    <dd class="col-sm-8">{{ $eventBooking->starts_at->format('F j, Y') }}</dd>

                    <dt class="col-sm-4">Time</dt>
                    <dd class="col-sm-8">
                        {{ $eventBooking->starts_at->format('g:i A') }}–{{ $eventBooking->ends_at->format('g:i A') }}
                        <span class="text-muted">({{ config('app.timezone') }})</span>
                    </dd>

                    <dt class="col-sm-4">Guests</dt>
                    <dd class="col-sm-8">{{ number_format($eventBooking->guest_count) }}</dd>

                    <dt class="col-sm-4">Venue price</dt>
                    <dd class="col-sm-8">
                        {{ $eventBooking->quoted_price !== null ? '$'.number_format((float) $eventBooking->quoted_price, 2) : 'Not specified' }}
                    </dd>

                    <dt class="col-sm-4">Special requests</dt>
                    <dd class="col-sm-8">{{ $eventBooking->special_requests ?: 'None' }}</dd>

                    @if($eventBooking->status_note)
                        <dt class="col-sm-4">Status note</dt>
                        <dd class="col-sm-8">{{ $eventBooking->status_note }}</dd>
                    @endif
                </dl>
            </div>

            @if($eventBooking->canBeCancelled())
                <div class="card-footer bg-white p-4 text-end">
                    <form method="POST" action="{{ route('event-reservations.cancel', $eventBooking) }}"
                          onsubmit="return confirm('Cancel this event reservation?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline-danger">Cancel Reservation</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
