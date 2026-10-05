@extends(request()->routeIs('management.facilities.*') ? 'layouts.management' : 'layouts.app')
@section('title', $facility->name)
@section('page-label', 'Facilities')
@section('content')
@php($managing = request()->routeIs('management.facilities.*'))
<section class="{{ $managing ? '' : 'content-section compact' }}"><div class="{{ $managing ? '' : 'container' }}">
    <div class="mb-4"><a href="{{ route($managing ? 'management.facilities.index' : 'facilities.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to facilities</a></div>
    <div class="row justify-content-center"><div class="col-xl-9"><div class="hotel-card p-4 p-lg-5"><div class="row align-items-center g-4"><div class="col-md-auto"><span class="empty-state-icon m-0" style="width:92px;height:92px;font-size:2rem"><i class="bi bi-stars"></i></span></div><div class="col"><div class="d-flex flex-wrap justify-content-between gap-3 align-items-start"><div><p class="section-kicker">Hotel amenity</p><h1 class="section-title">{{ $facility->name }}</h1></div><x-status-badge :status="match((string) $facility->status) { '1', 'open' => 'available', 'maintenance' => 'maintenance', default => 'unavailable' }" /></div><p class="section-copy">{{ $facility->description ?: 'This facility supports a comfortable and convenient hotel experience.' }}</p>@if($managing)<a href="{{ route('management.facilities.edit', $facility) }}" class="btn btn-hotel mt-3">Edit Facility</a>@else<a href="{{ route('rooms.index') }}" class="btn btn-outline-primary mt-3">Browse Rooms</a>@endif</div></div></div></div></div>
</div></section>
@endsection
