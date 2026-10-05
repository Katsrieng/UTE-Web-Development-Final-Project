@extends('layouts.app')
@section('title', 'Reserve an Event Venue')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">Your occasion starts here</p><h1>Request an event venue</h1><p>Tell us what you are planning. Hotel staff will review availability and respond to your request.</p></div></section>
<section class="content-section compact"><div class="container"><div class="row justify-content-center"><div class="col-xl-9">
    <div class="page-heading"><div><h1>Reservation details</h1><p>All dates and times use the hotel timezone: {{ config('app.timezone') }}.</p></div><a href="{{ route('event-reservations.index') }}" class="btn btn-outline-secondary">My Reservations</a></div>
    @if($errors->any())<div class="validation-summary mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please correct the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <form method="POST" action="{{ route('event-reservations.store') }}" class="card shadow-sm border-0">@csrf
        <div class="card-body p-4 p-lg-5"><div class="row g-4">
            <div class="col-12"><label for="venue_id" class="form-label">Venue</label><select id="venue_id" name="venue_id" class="form-select @error('venue_id') is-invalid @enderror" required><option value="">Choose a venue</option>@foreach($venues as $venue)<option value="{{ $venue->id }}" @selected((int)old('venue_id',$selectedVenueId)===$venue->id)>{{ $venue->name }} — up to {{ number_format($venue->capacity) }} guests @if($venue->price !== null) — ${{ number_format((float)$venue->price,2) }}@endif</option>@endforeach</select></div>
            <div class="col-md-7"><label for="event_type" class="form-label">Event type</label><select id="event_type" name="event_type" class="form-select @error('event_type') is-invalid @enderror" required><option value="">Choose an event type</option>@foreach($eventTypes as $eventType)<option value="{{ $eventType }}" @selected(old('event_type')===$eventType)>{{ ucfirst($eventType) }}</option>@endforeach</select></div>
            <div class="col-md-5"><label for="guest_count" class="form-label">Number of guests</label><input id="guest_count" type="number" min="1" name="guest_count" value="{{ old('guest_count') }}" class="form-control @error('guest_count') is-invalid @enderror" required></div>
            <div class="col-md-4"><label for="event_date" class="form-label">Event date</label><input id="event_date" type="date" min="{{ now()->toDateString() }}" name="event_date" value="{{ old('event_date') }}" class="form-control @error('event_date') is-invalid @enderror" required></div>
            <div class="col-md-4"><label for="start_time" class="form-label">Start time</label><input id="start_time" type="time" name="start_time" value="{{ old('start_time') }}" class="form-control @error('start_time') is-invalid @enderror" required></div>
            <div class="col-md-4"><label for="end_time" class="form-label">End time</label><input id="end_time" type="time" name="end_time" value="{{ old('end_time') }}" class="form-control @error('end_time') is-invalid @enderror" required></div>
            <div class="col-12"><label for="special_requests" class="form-label">Special requests <span class="text-muted fw-normal">(optional)</span></label><textarea id="special_requests" name="special_requests" rows="5" maxlength="2000" class="form-control @error('special_requests') is-invalid @enderror" placeholder="Setup, accessibility, catering, or other requests">{{ old('special_requests') }}</textarea><div class="form-text">Maximum 2,000 characters.</div></div>
        </div></div>
        <div class="card-footer bg-white d-flex justify-content-between p-4"><a href="{{ route('venues.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-hotel">Submit Request</button></div>
    </form>
</div></div></div></section>
@endsection
