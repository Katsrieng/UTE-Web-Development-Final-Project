@extends(request()->routeIs('management.rooms.*') ? 'layouts.management' : 'layouts.app')

@section('title', request()->routeIs('management.rooms.*') ? 'Manage Rooms' : 'Rooms')
@section('page-label', 'Rooms')

@section('content')
@php($managing = request()->routeIs('management.rooms.*'))

@unless($managing)
    <section class="page-hero">
        <div class="container">
            <p class="section-kicker">Stay your way</p>
            <h1>Rooms made for real comfort</h1>
            <p>Explore available rooms, thoughtful amenities, and rates for every kind of stay.</p>
        </div>
    </section>
@endunless

<section class="{{ $managing ? '' : 'content-section' }}">
    <div class="{{ $managing ? '' : 'container' }}">
        <div class="page-heading">
            <div>
                @if($managing)<p class="section-kicker">Hotel inventory</p>@endif
                <h1>{{ $managing ? 'Manage Rooms' : 'Our Rooms' }}</h1>
                <p>{{ $managing ? 'Track room details, facilities, rates, and operating status.' : 'Find a comfortable space for your next visit.' }}</p>
            </div>
            @if($managing)
                <div class="d-flex flex-wrap gap-2">

@staffroute('management.room-types.index')
<a href="{{ route('management.room-types.index') }}" class="btn btn-outline-secondary"><i class="bi bi-house-door me-1"></i> Room Types</a>
@endstaffroute


@staffroute('management.facilities.index')
<a href="{{ route('management.facilities.index') }}" class="btn btn-outline-secondary"><i class="bi bi-stars me-1"></i> Facilities</a>
@endstaffroute


@staffroute('management.rooms.create')
<a href="{{ route('management.rooms.create') }}" class="btn btn-hotel"><i class="bi bi-plus-lg me-1"></i> Add Room</a>
@endstaffroute

                </div>
            @endif
        </div>

        @unless($managing)
        <x-list-toolbar :action="route('rooms.index')" placeholder="Room type or name" :fields="['room_type_id'=>['label'=>'Room type','all'=>'All room types','options'=>$roomTypes->pluck('name','id')->all()], 'guests'=>['label'=>'Guests','type'=>'number','min'=>1,'step'=>1], 'min_price'=>['label'=>'Min / night ($)','type'=>'number','min'=>0,'step'=>'0.01'], 'max_price'=>['label'=>'Max / night ($)','type'=>'number','min'=>0,'step'=>'0.01'], 'facility_id'=>['label'=>'Facility','all'=>'All facilities','options'=>$filterFacilities->pluck('name','id')->all()]]" />
        <p class="small text-muted mb-3">Rates are per night. Check date availability when reserving a room.</p>
        @endunless

        @if($managing)
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th class="ps-4">Room</th><th>Type</th><th>Floor</th><th>Rate</th><th>Facilities</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
                        <tbody>
                            @forelse($rooms as $room)
                                <tr>
                                    <td class="ps-4"><strong>Room {{ $room->room_number }}</strong><small class="d-block text-muted">#{{ $room->id }}</small></td>
                                    <td>{{ $room->roomType->name ?? 'Not assigned' }}</td>
                                    <td>{{ $room->floor }}</td>
                                    <td class="fw-semibold">${{ number_format($room->price_per_night, 2) }}</td>
                                    <td><span class="text-muted small">{{ $room->facilities->isNotEmpty() ? $room->facilities->pluck('name')->join(', ') : 'None' }}</span></td>
                                    <td><x-status-badge :status="$room->status" /></td>
                                    <td class="text-end pe-4 text-nowrap">

@staffroute('management.rooms.show')
<a href="{{ route('management.rooms.show', $room) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
@endstaffroute


@staffroute('management.rooms.edit')
<a href="{{ route('management.rooms.edit', $room) }}" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
@endstaffroute


@staffroute('management.rooms.destroy')
<form action="{{ route('management.rooms.destroy', $room) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" data-confirm="Delete Room {{ $room->room_number }}? This cannot be undone."><i class="bi bi-trash"></i></button>
                                        </form>
@endstaffroute

                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7"><x-empty-state icon="bi-door-closed" title="No rooms found" message="Add the first room to begin managing hotel inventory.">
@staffroute('management.rooms.create')
<a href="{{ route('management.rooms.create') }}" class="btn btn-hotel">Add Room</a>
@endstaffroute
</x-empty-state></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="row g-4">
                @forelse($rooms as $room)
                    @php($roomImage = $room->coverImageUrl())
                    <div class="col-md-6 col-lg-4">
                        <article class="catalog-card {{ $roomImage ? 'has-image' : '' }}">
                            @if($roomImage)<img class="catalog-card-image" src="{{ $roomImage }}" alt="" loading="lazy" decoding="async" fetchpriority="low">@endif
                            @unless($roomImage)<div class="catalog-card-placeholder"><i class="bi bi-door-open"></i></div>@endunless
                            <div class="catalog-card-content">
                                <x-status-badge :status="$room->status" class="mb-3" />
                                <h2>Room {{ $room->room_number }}</h2>
                                <div class="catalog-meta"><span><i class="bi bi-house-door me-1"></i>{{ $room->roomType->name ?? 'Room' }}</span><span><i class="bi bi-people me-1"></i>{{ $room->roomType->capacity ?? '—' }} guests</span></div>
                                <div class="d-flex align-items-end justify-content-between gap-3">
                                    <div><small class="text-white-50">From</small><strong class="d-block fs-5">${{ number_format($room->price_per_night, 2) }} <small class="fw-normal fs-6">/ night</small></strong></div>
                                    <a href="{{ route('rooms.show', $room) }}" class="btn btn-sm btn-light">Details</a>
                                </div>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12"><x-list-no-results module="rooms" :clear="route('rooms.index')" /></div>
                @endforelse
            </div>
        @endif

        @if($rooms->hasPages())<div class="mt-4">{{ $rooms->links('pagination::bootstrap-5') }}</div>@endif
    </div>
</section>
@endsection
