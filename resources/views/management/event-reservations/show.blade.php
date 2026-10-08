@extends('layouts.management')

@section('title', 'Review Event Reservation #'.$eventBooking->id)
@section('page-label', 'Event Reservations')

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

@staffroute('management.event-reservations.index')
<a href="{{ route('management.event-reservations.index') }}" class="text-decoration-none">&larr; Event reservations</a>
@endstaffroute

</div>

<div class="row g-4">
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
                    <dt class="col-sm-4">Customer</dt>
                    <dd class="col-sm-8">
                        {{ $eventBooking->user?->name ?? 'Deleted user' }}
                        @if($eventBooking->user)
                            <br><a href="mailto:{{ $eventBooking->user->email }}">{{ $eventBooking->user->email }}</a>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Event type</dt>
                    <dd class="col-sm-8">{{ ucfirst($eventBooking->event_type) }}</dd>

                    <dt class="col-sm-4">Date and time</dt>
                    <dd class="col-sm-8">
                        {{ $eventBooking->starts_at->format('F j, Y, g:i A') }}–{{ $eventBooking->ends_at->format('g:i A') }}
                        <span class="text-muted">({{ config('app.timezone') }})</span>
                    </dd>

                    <dt class="col-sm-4">Guest count</dt>
                    <dd class="col-sm-8">{{ number_format($eventBooking->guest_count) }} / {{ number_format($eventBooking->venue->capacity) }}</dd>

                    <dt class="col-sm-4">Quoted venue price</dt>
                    <dd class="col-sm-8">
                        {{ $eventBooking->quoted_price !== null ? '$'.number_format((float) $eventBooking->quoted_price, 2) : 'Not specified' }}
                    </dd>

                    <dt class="col-sm-4">Special requests</dt>
                    <dd class="col-sm-8">{{ $eventBooking->special_requests ?: 'None' }}</dd>

                    @if($eventBooking->status_note)
                        <dt class="col-sm-4">Status note</dt>
                        <dd class="col-sm-8">{{ $eventBooking->status_note }}</dd>
                    @endif

                    @if($eventBooking->processed_at)
                        <dt class="col-sm-4">Last processed</dt>
                        <dd class="col-sm-8">
                            {{ $eventBooking->processed_at->format('M j, Y g:i A') }}
                            by {{ $eventBooking->processedBy?->name ?? 'Deleted user' }}
                        </dd>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5">Reservation actions</h2>
                <div class="mb-3">@include('management.event-reservations._record-actions')</div>
                @if($reason = $eventBooking->staffEditBlockReason())<p class="small text-muted"><strong>Edit unavailable:</strong> {{ $reason }}</p>@endif
                @if($reason = $eventBooking->staffDeleteBlockReason())<p class="small text-muted"><strong>Delete unavailable:</strong> {{ $reason }}</p>@endif

                @can('approve', $eventBooking)

@staffroute('management.event-reservations.approve')
<form method="POST" action="{{ route('management.event-reservations.approve', $eventBooking) }}" class="mb-4">
                        @csrf
                        @method('PATCH')
                        <label for="approve_note" class="form-label">Approval note <span class="text-muted">(optional)</span></label>
                        <textarea id="approve_note" name="status_note" rows="2" maxlength="1000" class="form-control mb-2">{{ old('status_note') }}</textarea>
                        <button type="submit" class="btn btn-success w-100">Approve Reservation</button>
                    </form>
@endstaffroute

                @endcan

                @can('reject', $eventBooking)

@staffroute('management.event-reservations.reject')
<form method="POST" action="{{ route('management.event-reservations.reject', $eventBooking) }}" class="mb-4">
                        @csrf
                        @method('PATCH')
                        <label for="reject_note" class="form-label">Rejection reason</label>
                        <textarea id="reject_note" name="status_note" rows="3" maxlength="1000" required
                                  class="form-control @error('status_note') is-invalid @enderror mb-2">{{ old('status_note') }}</textarea>
                        @error('status_note')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-outline-danger w-100">Reject Reservation</button>
                    </form>
@endstaffroute

                @endcan

                @can('cancel', $eventBooking)

@staffroute('management.event-reservations.cancel')
<form method="POST" action="{{ route('management.event-reservations.cancel', $eventBooking) }}"
                          onsubmit="return confirm('Cancel this event reservation?');">
                        @csrf
                        @method('PATCH')
                        <label for="cancel_note" class="form-label">Cancellation note <span class="text-muted">(optional)</span></label>
                        <textarea id="cancel_note" name="status_note" rows="2" maxlength="1000" class="form-control mb-2"></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100">Cancel Reservation</button>
                    </form>
@endstaffroute

                @endcan


            </div>
        </div>
    </div>
</div>
@endsection
