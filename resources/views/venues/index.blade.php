@extends('layouts.app')
@section('title', 'Event Venues')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">Meet, celebrate, remember</p><h1>A venue for every occasion</h1><p>Elegant settings for weddings, focused spaces for meetings, and memorable places for private parties.</p></div></section>

<section class="content-section"><div class="container">
    <div class="page-heading"><div><p class="section-kicker">Events &amp; venues</p><h1>Find your perfect space</h1><p>Compare venue capacity, location, event types, and pricing.</p></div>@auth @if(auth()->user()->isCustomer())<a href="{{ route('event-reservations.index') }}" class="btn btn-outline-primary"><i class="bi bi-calendar2-check me-1"></i> My Reservations</a>@endif @endauth</div>

    <form method="GET" action="{{ route('venues.index') }}" class="filter-panel mb-5">
        <div class="row g-3 align-items-end"><div class="col-md-8"><label for="event_type" class="form-label">What are you planning?</label><select id="event_type" name="event_type" class="form-select"><option value="">All event types</option>@foreach($eventTypes as $eventType)<option value="{{ $eventType }}" @selected(request('event_type') === $eventType)>{{ ucfirst($eventType) }}</option>@endforeach</select></div><div class="col-md-4 d-flex gap-2"><button type="submit" class="btn btn-hotel flex-grow-1"><i class="bi bi-search me-1"></i> Find venues</button>@if(request()->filled('event_type'))<a href="{{ route('venues.index') }}" class="btn btn-outline-secondary">Reset</a>@endif</div></div>
    </form>

    <div class="row g-4">
        @forelse($venues as $venue)
            <div class="col-md-6 col-lg-4">
                <article class="catalog-card {{ !empty($venue->images) ? 'has-image' : '' }}" @if(!empty($venue->images)) style="background-image:url('{{ asset('storage/'.$venue->images[0]) }}')" @endif>
                    @if(empty($venue->images))<div class="catalog-card-placeholder"><i class="bi bi-building"></i></div>@endif
                    <div class="catalog-card-content">
                        <div class="d-flex flex-wrap gap-2 mb-3">@foreach($venue->event_types as $eventType)<span class="badge rounded-pill text-bg-light">{{ ucfirst($eventType) }}</span>@endforeach</div>
                        <h2>{{ $venue->name }}</h2>
                        <div class="catalog-meta"><span><i class="bi bi-geo-alt me-1"></i>{{ $venue->location }}</span><span><i class="bi bi-people me-1"></i>Up to {{ number_format($venue->capacity) }}</span></div>
                        <div class="d-flex justify-content-between align-items-end gap-3"><div>@if($venue->price !== null)<small class="text-white-50">Venue price</small><strong class="d-block fs-5">${{ number_format((float)$venue->price, 2) }}</strong>@else<strong>Contact hotel</strong>@endif</div><a href="{{ route('venues.show', $venue) }}" class="btn btn-sm btn-light">View venue</a></div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><x-empty-state icon="bi-building" title="No venues found" message="Try another event type or check back later.">@if(request()->filled('event_type'))<a href="{{ route('venues.index') }}" class="btn btn-hotel">View all venues</a>@endif</x-empty-state></div>
        @endforelse
    </div>
    @if($venues->hasPages())<div class="mt-4">{{ $venues->links('pagination::bootstrap-5') }}</div>@endif
</div></section>
@endsection
