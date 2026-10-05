@extends('layouts.app')
@section('title', $venue->name)
@section('content')
<div class="breadcrumb-bar"><div class="container"><a href="{{ route('venues.index') }}"><i class="bi bi-arrow-left me-1"></i> Back to venues</a></div></div>
<section class="content-section compact"><div class="container"><div class="row g-5 align-items-start">
    <div class="col-lg-7">
        @if(!empty($venue->images))
            <div class="detail-gallery-main mb-3"><img src="{{ asset('storage/'.$venue->images[0]) }}" alt="{{ $venue->name }}"></div>
            @if(count($venue->images) > 1)<div class="row g-3">@foreach(array_slice($venue->images, 1) as $image)<div class="col-6"><img src="{{ asset('storage/'.$image) }}" class="img-fluid rounded w-100" style="height:180px;object-fit:cover" alt="{{ $venue->name }} image {{ $loop->iteration + 1 }}"></div>@endforeach</div>@endif
        @else
            <div class="detail-gallery-main"><div class="detail-placeholder"><i class="bi bi-building"></i></div></div>
        @endif
        <div class="mt-5"><p class="section-kicker">About the venue</p><h1 class="section-title">{{ $venue->name }}</h1><p class="section-copy">{{ $venue->description ?: 'Contact the hotel for more information about this venue.' }}</p></div>
    </div>
    <div class="col-lg-5"><aside class="detail-panel">
        <p class="section-kicker">Reserve your date</p><h2 class="h2">{{ $venue->name }}</h2><p class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $venue->location }}</p>
        <div class="d-flex flex-wrap gap-2 my-3">@foreach($venue->event_types as $eventType)<span class="badge rounded-pill text-bg-light border px-3 py-2">{{ ucfirst($eventType) }}</span>@endforeach</div>
        <dl class="detail-list"><div><dt>Capacity</dt><dd>Up to {{ number_format($venue->capacity) }} guests</dd></div><div><dt>Venue price</dt><dd>{{ $venue->price !== null ? '$'.number_format((float)$venue->price, 2) : 'Contact hotel' }}</dd></div></dl>
        @auth
            @if(auth()->user()->isCustomer())<a href="{{ route('event-reservations.create', ['venue' => $venue->id]) }}" class="btn btn-hotel w-100"><i class="bi bi-calendar-plus me-1"></i> Request this venue</a>@elseif(auth()->user()->hasRole('admin','staff'))<a href="{{ route('management.venues.show', $venue) }}" class="btn btn-hotel w-100">Open management view</a>@endif
        @else<a href="{{ route('login') }}" class="btn btn-hotel w-100">Sign in to reserve</a><p class="text-muted small text-center mt-3 mb-0">Customer accounts can submit event requests online.</p>@endauth
    </aside></div>
</div></div></section>
@endsection
