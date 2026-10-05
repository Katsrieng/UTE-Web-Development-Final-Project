@extends('layouts.app')

@section('title', $venue->name)

@section('content')
<div class="mb-3">
    <a href="{{ route('venues.index') }}" class="text-decoration-none">&larr; Back to venues</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        @if(! empty($venue->images))
            <div class="row g-2">
                @foreach($venue->images as $image)
                    <div class="{{ $loop->first ? 'col-12' : 'col-6' }}">
                        <img src="{{ asset('storage/'.$image) }}"
                             class="img-fluid rounded object-fit-cover w-100"
                             style="{{ $loop->first ? 'height: 420px;' : 'height: 200px;' }}"
                             alt="{{ $venue->name }} image {{ $loop->iteration }}">
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded bg-dark-subtle d-flex align-items-center justify-content-center text-secondary"
                 style="min-height: 420px;">
                <span class="fs-4">Venue image coming soon</span>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h1 class="h2">{{ $venue->name }}</h1>
                <p class="text-muted">{{ $venue->location }}</p>

                <dl class="row mb-3">
                    <dt class="col-5">Capacity</dt>
                    <dd class="col-7">Up to {{ number_format($venue->capacity) }} guests</dd>

                    <dt class="col-5">Venue price</dt>
                    <dd class="col-7">
                        {{ $venue->price !== null ? '$'.number_format((float) $venue->price, 2) : 'Contact hotel' }}
                    </dd>
                </dl>

                <div class="mb-3">
                    @foreach($venue->event_types as $eventType)
                        <span class="badge text-bg-primary me-1">{{ ucfirst($eventType) }}</span>
                    @endforeach
                </div>

                <p class="mb-4">{{ $venue->description ?: 'Contact the hotel for more information about this venue.' }}</p>

                @auth
                    @if(auth()->user()->isCustomer())
                        <a href="{{ route('event-reservations.create', ['venue' => $venue->id]) }}"
                           class="btn btn-primary w-100">Reserve this venue</a>
                    @elseif(auth()->user()->hasRole('admin', 'staff'))
                        <a href="{{ route('management.venues.show', $venue) }}"
                           class="btn btn-outline-primary w-100">Open management view</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary w-100">Sign in to reserve</a>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
