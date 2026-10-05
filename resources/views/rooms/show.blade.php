@extends(request()->routeIs('management.rooms.*') ? 'layouts.management' : 'layouts.app')
@section('title', 'Room '.$room->room_number)
@section('page-label', 'Rooms')
@section('content')
@php($managing = request()->routeIs('management.rooms.*'))
@php($roomImage = $room->image ? (str_starts_with($room->image, 'images/') ? asset($room->image) : asset('storage/'.$room->image)) : null)
<section class="{{ $managing ? '' : 'content-section compact' }}"><div class="{{ $managing ? '' : 'container' }}">
    <div class="mb-4"><a href="{{ route($managing ? 'management.rooms.index' : 'rooms.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to rooms</a></div>
    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
            <div class="detail-gallery-main">@if($roomImage)<img src="{{ $roomImage }}" alt="Room {{ $room->room_number }}">@else<div class="detail-placeholder"><i class="bi bi-door-open"></i></div>@endif</div>
            <div class="mt-4"><p class="section-kicker">Room details</p><h1 class="section-title">Room {{ $room->room_number }}</h1><p class="section-copy">{{ $room->description ?: 'A comfortable hotel room prepared for a relaxing stay.' }}</p></div>
            @if($room->facilities->isNotEmpty())<div class="mt-4"><h2 class="h4 mb-3">Included facilities</h2><div class="row g-3">@foreach($room->facilities as $facility)<div class="col-sm-6"><div class="p-3 bg-white border rounded"><i class="bi bi-check-circle text-success me-2"></i>{{ $facility->name }}</div></div>@endforeach</div></div>@endif
        </div>
        <div class="col-lg-5">
            <aside class="detail-panel">
                <div class="d-flex justify-content-between align-items-start gap-3"><div><p class="section-kicker">{{ $room->roomType->name ?? 'Hotel room' }}</p><h2 class="h3 mb-0">Room {{ $room->room_number }}</h2></div><x-status-badge :status="$room->status" /></div>
                <dl class="detail-list"><div><dt>Nightly rate</dt><dd>${{ number_format($room->price_per_night, 2) }}</dd></div><div><dt>Floor</dt><dd>{{ $room->floor }}</dd></div><div><dt>Capacity</dt><dd>{{ $room->roomType->capacity ?? '—' }} guests</dd></div><div><dt>Bed</dt><dd>{{ $room->roomType->bed_type ?? 'Contact hotel' }}</dd></div></dl>
                @if($managing)
                    <a href="{{ route('management.rooms.edit', $room) }}" class="btn btn-hotel w-100">Edit Room</a>
                @elseif(auth()->guest())
                    <a href="{{ route('login') }}" class="btn btn-hotel w-100">Sign in to continue</a>
                    <p class="text-muted small text-center mt-3 mb-0">Online room booking will be available when the booking module is connected.</p>
                @else
                    <p class="text-muted small text-center mb-0">You are viewing the public room catalogue. Online room booking will be available when the booking module is connected.</p>
                @endif
            </aside>
        </div>
    </div>
</div></section>
@endsection
