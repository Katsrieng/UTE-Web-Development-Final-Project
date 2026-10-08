@extends('layouts.management')
@section('title', 'Edit Event Reservation #'.$eventBooking->id)
@section('page-label', 'Event Reservations')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Reservation #{{ $eventBooking->id }}</p><h1 class="h3">Edit Event Reservation</h1><p>{{ $eventBooking->user?->name ?? 'Deleted user' }} · Pending request</p></div><a class="btn btn-outline-secondary" href="{{ route('management.event-reservations.show', $eventBooking) }}">Back to Reservation</a></div>
@if($errors->any())<div class="validation-summary mb-3" role="alert"><div><strong>Please correct the following:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
<div class="row"><div class="col-xl-9"><form method="POST" action="{{ route('management.event-reservations.update', $eventBooking) }}" class="card border-0 shadow-sm">
@csrf @method('PATCH')
<div class="card-body p-4"><div class="row g-3">
<div class="col-md-8"><label class="form-label" for="venue_id">Venue</label><select id="venue_id" name="venue_id" required class="form-select">@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected((int)old('venue_id', $eventBooking->venue_id) === $venue->id)>{{ $venue->name }}@if(!$venue->is_active) (Archived)@endif · {{ $venue->capacity }} guests @if($venue->price !== null) · ${{ number_format($venue->price, 2) }}@endif</option>@endforeach</select><div class="form-text">Changing venue updates the quote to its current price.</div></div>
<div class="col-md-4"><label class="form-label" for="guest_count">Guest count</label><input id="guest_count" name="guest_count" type="number" min="1" max="100000" step="1" required class="form-control" value="{{ old('guest_count', $eventBooking->guest_count) }}"></div>
<div class="col-12"><label class="form-label" for="event_type">Event type</label><select id="event_type" name="event_type" required class="form-select">@foreach($eventTypes as $type)<option value="{{ $type }}" @selected(old('event_type', $eventBooking->event_type) === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label" for="event_date">Event date</label><input id="event_date" name="event_date" type="date" min="{{ today()->toDateString() }}" required class="form-control" value="{{ old('event_date', $eventBooking->starts_at->format('Y-m-d')) }}"></div>
<div class="col-md-4"><label class="form-label" for="start_time">Start time</label><input id="start_time" name="start_time" type="time" required class="form-control" value="{{ old('start_time', $eventBooking->starts_at->format('H:i')) }}"></div>
<div class="col-md-4"><label class="form-label" for="end_time">End time</label><input id="end_time" name="end_time" type="time" required class="form-control" value="{{ old('end_time', $eventBooking->ends_at->format('H:i')) }}"></div>
<div class="col-12"><small class="text-muted">Hotel timezone: {{ config('app.timezone') }}. The end time must be later on the same day.</small></div>
<div class="col-12"><label class="form-label" for="special_requests">Special requests</label><textarea id="special_requests" name="special_requests" maxlength="2000" rows="3" class="form-control">{{ old('special_requests', $eventBooking->special_requests) }}</textarea></div>
</div></div><div class="card-footer bg-white px-4 py-3 d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('management.event-reservations.show', $eventBooking) }}">Cancel</a><button type="submit" class="btn btn-hotel">Save Changes</button></div>
</form></div></div>
@endsection
