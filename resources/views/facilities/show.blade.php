@extends(request()->routeIs('management.facilities.*') ? 'layouts.management' : 'layouts.app')
@section('title', $facility->name)
@section('page-label', 'Facilities')
@section('content')
@php($managing = request()->routeIs('management.facilities.*'))
<section class="{{ $managing ? '' : 'content-section compact' }}"><div class="{{ $managing ? '' : 'container' }}">
    <div class="mb-4"><a href="{{ route($managing ? 'management.facilities.index' : 'facilities.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to facilities</a></div>
    <div class="row justify-content-center"><div class="col-xl-9">
        <div class="hotel-card facility-detail">
            @if($facility->imageUrl())
                <img class="facility-detail-photo" src="{{ $facility->imageUrl() }}" alt="{{ $facility->name }}">
            @else
                <div class="facility-detail-photo facility-photo-placeholder"><i class="bi bi-stars" aria-hidden="true"></i></div>
            @endif
            <div class="facility-detail-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                    <div><p class="section-kicker">Hotel amenity</p><h1 class="section-title">{{ $facility->name }}</h1></div>
                    <x-status-badge :status="$facility->status" context="facility" />
                </div>
                <p class="section-copy">{{ $facility->description ?: 'This facility supports a comfortable and convenient hotel experience.' }}</p>
                <dl class="facility-detail-facts">
                    @if($facility->location)<div><dt>Location</dt><dd>{{ $facility->location }}</dd></div>@endif
                    @if($facility->opening_time)<div><dt>Opens</dt><dd>{{ \Carbon\Carbon::parse($facility->opening_time)->format('g:i A') }}</dd></div>@endif
                    @if($facility->closing_time)<div><dt>Closes</dt><dd>{{ \Carbon\Carbon::parse($facility->closing_time)->format('g:i A') }}</dd></div>@endif
                    <div><dt>Price</dt><dd>{{ (float) $facility->price > 0 ? '$'.number_format((float) $facility->price, 2) : 'Complimentary' }}</dd></div>
                </dl>
                @if($managing)
                    @staffroute('management.facilities.edit')<a href="{{ route('management.facilities.edit', $facility) }}" class="btn btn-hotel mt-3">Edit Facility</a>@endstaffroute
                @else
                    <a href="{{ route('rooms.index') }}" class="btn btn-outline-primary mt-3">Browse Rooms</a>
                @endif
            </div>
        </div>
    </div></div>
</div></section>
@endsection
