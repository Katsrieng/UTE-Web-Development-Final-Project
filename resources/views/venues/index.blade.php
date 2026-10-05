@extends('layouts.app')

@section('title', 'Event Venues')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
        <p class="text-primary fw-semibold mb-1">Events &amp; Venues</p>
        <h1 class="h2 mb-2">Find a venue for your occasion</h1>
        <p class="text-muted mb-0">Explore spaces for weddings, meetings, and private parties.</p>
    </div>

    @auth
        @if(auth()->user()->isCustomer())
            <a href="{{ route('event-reservations.index') }}" class="btn btn-outline-primary">My Reservations</a>
        @endif
    @endauth
</div>

<form method="GET" action="{{ route('venues.index') }}" class="card card-body mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-md-8">
            <label for="event_type" class="form-label">Event type</label>
            <select id="event_type" name="event_type" class="form-select">
                <option value="">All event types</option>
                @foreach($eventTypes as $eventType)
                    <option value="{{ $eventType }}" @selected(request('event_type') === $eventType)>
                        {{ ucfirst($eventType) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Filter venues</button>
            @if(request()->filled('event_type'))
                <a href="{{ route('venues.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </div>
    </div>
</form>

<div class="row g-4">
    @forelse($venues as $venue)
        <div class="col-md-6 col-lg-4">
            <article class="card h-100 shadow-sm border-0 overflow-hidden">
                @if(! empty($venue->images))
                    <img src="{{ asset('storage/'.$venue->images[0]) }}"
                         class="card-img-top object-fit-cover"
                         style="height: 220px;"
                         alt="{{ $venue->name }}">
                @else
                    <div class="bg-dark-subtle d-flex align-items-center justify-content-center text-secondary"
                         style="height: 220px;">
                        <span class="fs-5">Venue image coming soon</span>
                    </div>
                @endif

                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between gap-3 mb-2">
                        <h2 class="h5 card-title mb-0">{{ $venue->name }}</h2>
                        @if($venue->price !== null)
                            <span class="fw-semibold text-nowrap">${{ number_format((float) $venue->price, 2) }}</span>
                        @endif
                    </div>
                    <p class="text-muted small mb-2">{{ $venue->location }} &middot; Up to {{ number_format($venue->capacity) }} guests</p>
                    <p class="card-text">{{ Illuminate\Support\Str::limit($venue->description, 130) }}</p>

                    <div class="mb-3">
                        @foreach($venue->event_types as $eventType)
                            <span class="badge text-bg-light border me-1">{{ ucfirst($eventType) }}</span>
                        @endforeach
                    </div>

                    <a href="{{ route('venues.show', $venue) }}" class="btn btn-primary mt-auto">View venue</a>
                </div>
            </article>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-light border text-center py-5 mb-0">
                <h2 class="h5">No venues found</h2>
                <p class="text-muted mb-0">Try another event type or check back later.</p>
            </div>
        </div>
    @endforelse
</div>

@if($venues->hasPages())
    <div class="mt-4">
        {{ $venues->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
