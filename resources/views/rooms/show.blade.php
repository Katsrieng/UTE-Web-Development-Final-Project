@extends(request()->routeIs('management.rooms.*') ? 'layouts.management' : 'layouts.app')
@section('title', 'Room '.$room->room_number)
@section('page-label', 'Rooms')
@section('content')
@php($managing = request()->routeIs('management.rooms.*'))
@php($roomImage = $room->coverImageUrl())
@php($coverPhoto = $room->images->firstWhere('is_primary', true) ?? $room->images->first())
@php($carouselPhotos = $room->images->sortBy(fn ($photo) => $photo->is_primary ? 0 : 1)->values())
<section class="{{ $managing ? '' : 'content-section compact' }}"><div class="{{ $managing ? '' : 'container' }}">
    <div class="mb-4"><a href="{{ route($managing ? 'management.rooms.index' : 'rooms.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to rooms</a></div>
    <div class="row g-4 align-items-start">
        <div class="col-lg-7">
            <div data-room-gallery @unless($managing) tabindex="0" aria-label="Room photo gallery" @endunless>
                <div class="detail-gallery-main">
                    @if($roomImage)
                        @if($managing)
                            <img data-room-main src="{{ $roomImage }}" alt="{{ $coverPhoto?->alt_text ?: 'Room '.$room->room_number }}">
                        @else
                            <button type="button" class="room-gallery-zoom-trigger" data-bs-toggle="modal" data-bs-target="#roomPhotoModal" aria-label="View selected room photo larger">
                                <img data-room-main src="{{ $roomImage }}" alt="{{ $coverPhoto?->alt_text ?: 'Room '.$room->room_number }}">
                            </button>
                        @endif
                    @else
                        <div class="detail-placeholder"><i class="bi bi-door-open"></i></div>
                    @endif
                    @if(! $managing && $carouselPhotos->count() > 1)
                        <button type="button" class="room-gallery-arrow room-gallery-arrow-prev" data-room-prev aria-label="Previous room photo"><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                        <button type="button" class="room-gallery-arrow room-gallery-arrow-next" data-room-next aria-label="Next room photo"><i class="bi bi-chevron-right" aria-hidden="true"></i></button>
                        <span class="room-gallery-counter" data-room-counter aria-live="polite">1 / {{ $carouselPhotos->count() }}</span>
                    @endif
                </div>
                @if($managing && $room->images->isNotEmpty())
                    <div class="room-gallery-thumbnails" aria-label="Room photos">
                        @foreach($room->images as $photo)<button type="button" data-room-thumbnail data-image-src="{{ $photo->url() }}" data-image-alt="{{ $photo->alt_text ?: 'Room '.$room->room_number.' photo '.($loop->iteration) }}" aria-label="View {{ $photo->alt_text ?: 'room photo '.$loop->iteration }}{{ $photo->is_primary ? ', cover photo' : '' }}" aria-pressed="{{ $photo->id === $coverPhoto->id ? 'true' : 'false' }}"><img src="{{ $photo->url() }}" alt="{{ $photo->alt_text ?: 'Room photo '.$loop->iteration }}" loading="lazy">@if($photo->is_primary)<span class="room-gallery-cover-label">Cover</span>@endif</button>@endforeach
                    </div>
                @endif
                @unless($managing)
                    @foreach($carouselPhotos as $photo)
                        <span hidden data-room-slide data-image-src="{{ $photo->url() }}" data-image-alt="{{ $photo->alt_text ?: 'Room '.$room->room_number.' photo '.$loop->iteration }}"></span>
                    @endforeach
                @endunless
            </div>
            @if($roomImage && ! $managing)
                <div class="modal fade room-photo-modal" id="roomPhotoModal" tabindex="-1" aria-label="Room photo preview">
                    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
                        <button type="button" class="btn-close btn-close-white room-photo-modal-close" data-bs-dismiss="modal" aria-label="Close photo preview"></button>
                        <img data-room-zoom-image src="{{ $roomImage }}" alt="{{ $coverPhoto?->alt_text ?: 'Room '.$room->room_number }}">
                    </div></div>
                </div>
            @endif
            <div class="mt-4"><p class="section-kicker">Room details</p><h1 class="section-title">Room {{ $room->room_number }}</h1><p class="section-copy">{{ $room->description ?: 'A comfortable hotel room prepared for a relaxing stay.' }}</p></div>
            @if($room->facilities->isNotEmpty())<div class="mt-4"><h2 class="h4 mb-3">Included facilities</h2><div class="row g-3">@foreach($room->facilities as $facility)<div class="col-sm-6"><div class="p-3 bg-white border rounded"><i class="bi bi-check-circle text-success me-2"></i>{{ $facility->name }}</div></div>@endforeach</div></div>@endif
        </div>
        <div class="col-lg-5">
            <aside class="detail-panel">
                <div class="d-flex justify-content-between align-items-start gap-3"><div><p class="section-kicker">{{ $room->roomType->name ?? 'Hotel room' }}</p><h2 class="h3 mb-0">Room {{ $room->room_number }}</h2></div><x-status-badge :status="$room->status" /></div>
                <dl class="detail-list"><div><dt>Nightly rate</dt><dd>${{ number_format($room->price_per_night, 2) }}</dd></div><div><dt>Floor</dt><dd>{{ $room->floor }}</dd></div><div><dt>Capacity</dt><dd>{{ $room->roomType->capacity ?? '—' }} guests</dd></div><div><dt>Bed</dt><dd>{{ $room->roomType->bed_type ?? 'Contact hotel' }}</dd></div></dl>
                @if($managing)

@staffroute('management.rooms.edit')
<a href="{{ route('management.rooms.edit', $room) }}" class="btn btn-hotel w-100">Edit Room</a>
@endstaffroute

                @elseif(auth()->guest())
                    <a href="{{ route('customer.bookings.create', $room) }}" class="btn btn-hotel w-100">Sign in to reserve this room</a>
                @elseif(auth()->user()->isCustomer() && auth()->user()->is_active)
                    <a href="{{ route('customer.bookings.create', $room) }}" class="btn btn-hotel w-100">Reserve this room</a>
                @elseif(auth()->user()->isCustomer())
                    <p class="text-muted small text-center mb-0">Your account is inactive. Please contact the hotel administrator.</p>
                @else
                    <p class="text-muted small text-center mb-0">You are viewing the public room catalogue.</p>
                @endif
            </aside>
        </div>
    </div>
</div></section>
@endsection
