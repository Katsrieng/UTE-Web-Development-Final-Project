@extends(request()->routeIs('management.room-types.*') ? 'layouts.management' : 'layouts.app')
@section('title', $roomType->name)
@section('page-label', 'Room Types')
@section('content')
@php($managing = request()->routeIs('management.room-types.*'))
@php($roomTypeImage = $roomType->image ? (str_starts_with($roomType->image, '/storage/') ? asset(ltrim($roomType->image, '/')) : asset('storage/'.$roomType->image)) : null)
<section class="{{ $managing ? '' : 'content-section compact' }}"><div class="{{ $managing ? '' : 'container' }}">
    <div class="mb-4"><a href="{{ route($managing ? 'management.room-types.index' : 'room-types.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to room types</a></div>
    @if($roomTypeImage)<div class="detail-gallery-main mb-4"><img src="{{ $roomTypeImage }}" alt="{{ $roomType->name }}"></div>@endif
    <div class="page-heading"><div><p class="section-kicker">From ${{ number_format($roomType->base_price, 2) }} per night</p><h1>{{ $roomType->name }}</h1><p>{{ $roomType->description ?: 'A comfortable room category prepared for hotel guests.' }}</p></div>@if($managing)
@staffroute('management.room-types.edit')
<a href="{{ route('management.room-types.edit', $roomType) }}" class="btn btn-hotel">Edit Room Type</a>
@endstaffroute
@endif</div>
    <div class="row g-4 mb-5"><div class="col-md-4"><div class="metric-card"><span class="metric-icon"><i class="bi bi-people"></i></span><small>Maximum capacity</small><strong>{{ $roomType->capacity }}</strong><span class="text-muted">guests</span></div></div><div class="col-md-4"><div class="metric-card"><span class="metric-icon"><i class="bi bi-moon-stars"></i></span><small>Bed configuration</small><strong class="fs-4">{{ $roomType->bed_type ?: 'Flexible' }}</strong></div></div><div class="col-md-4"><div class="metric-card"><span class="metric-icon"><i class="bi bi-door-open"></i></span><small>Rooms in category</small><strong>{{ $roomType->rooms->count() }}</strong></div></div></div>
    <h2 class="h3 mb-4">Rooms in this category</h2><div class="row g-4">@forelse($roomType->rooms as $room)<div class="col-md-6 col-lg-4"><div class="hotel-card p-4"><div class="d-flex justify-content-between gap-3 mb-3"><h3 class="h5 mb-0">Room {{ $room->room_number }}</h3><x-status-badge :status="$room->status" /></div><p class="text-muted mb-3">Floor {{ $room->floor }}</p><strong class="d-block mb-3">${{ number_format($room->price_per_night, 2) }} / night</strong><a href="{{ route($managing ? 'management.rooms.show' : 'rooms.show', $room) }}" class="btn btn-sm btn-outline-primary">View room</a></div></div>@empty<div class="col-12"><x-empty-state icon="bi-door-closed" title="No rooms assigned" message="There are no rooms in this category yet." /></div>@endforelse</div>
</div></section>
@endsection
