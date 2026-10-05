@extends(request()->routeIs('management.room-types.*') ? 'layouts.management' : 'layouts.app')

@section('title', 'Room Types')
@section('page-label', 'Room Types')

@section('content')
@php($managing = request()->routeIs('management.room-types.*'))

@unless($managing)
    <section class="page-hero"><div class="container"><p class="section-kicker">Choose your comfort</p><h1>Room types for every stay</h1><p>Compare capacity, bed options, and starting rates before exploring individual rooms.</p></div></section>
@endunless

<section class="{{ $managing ? '' : 'content-section' }}">
    <div class="{{ $managing ? '' : 'container' }}">
        <div class="page-heading">
            <div>@if($managing)<p class="section-kicker">Room catalogue</p>@endif<h1>{{ $managing ? 'Manage Room Types' : 'Explore Room Types' }}</h1><p>{{ $managing ? 'Maintain room categories, pricing, capacity, and catalogue images.' : 'Find the space that best suits your visit.' }}</p></div>
            @if($managing)<div class="d-flex gap-2"><a href="{{ route('management.rooms.index') }}" class="btn btn-outline-secondary">Rooms</a><a href="{{ route('management.room-types.create') }}" class="btn btn-hotel"><i class="bi bi-plus-lg me-1"></i> Add Room Type</a></div>@endif
        </div>

        @if($managing)
            <div class="table-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th class="ps-4">Room type</th><th>Base Price</th><th>Capacity</th><th>Bed Type</th><th>Rooms</th><th class="text-end pe-4">Actions</th></tr></thead>
                        <tbody>
                            @forelse($roomTypes as $type)
                                @php($typeImage = $type->image ? (str_starts_with($type->image, '/storage/') ? asset(ltrim($type->image, '/')) : asset('storage/'.$type->image)) : null)
                                <tr>
                                    <td class="ps-4"><div class="d-flex align-items-center gap-3">@if($typeImage)<img src="{{ $typeImage }}" alt="" class="table-thumb" loading="lazy">@else<span class="table-thumb table-thumb-placeholder"><i class="bi bi-house-door"></i></span>@endif<div><strong>{{ $type->name }}</strong><small class="d-block text-muted">{{ Illuminate\Support\Str::limit($type->description, 55) }}</small></div></div></td>
                                    <td class="fw-semibold">${{ number_format($type->base_price, 2) }}</td>
                                    <td>{{ $type->capacity }} guests</td>
                                    <td>{{ $type->bed_type ?: '—' }}</td>
                                    <td>{{ $type->rooms_count }}</td>
                                    <td class="text-end pe-4 text-nowrap"><a href="{{ route('management.room-types.show', $type) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a> <a href="{{ route('management.room-types.edit', $type) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a> <form action="{{ route('management.room-types.destroy', $type) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Delete {{ $type->name }}?" type="submit"><i class="bi bi-trash"></i></button></form></td>
                                </tr>
                            @empty
                                <tr><td colspan="6"><x-empty-state icon="bi-house-door" title="No room types found" message="Create a room type to organize hotel inventory." /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="row g-4">
                @forelse($roomTypes as $type)
                    @php($typeImage = $type->image ? (str_starts_with($type->image, '/storage/') ? asset(ltrim($type->image, '/')) : asset('storage/'.$type->image)) : null)
                    <div class="col-md-6 col-lg-4">
                        <article class="hotel-card room-type-card overflow-hidden">
                            @if($typeImage)<img src="{{ $typeImage }}" alt="{{ $type->name }}" class="room-type-card-image" loading="lazy" decoding="async">@endif
                            <div class="p-4">
                                <p class="section-kicker mb-2">From ${{ number_format($type->base_price, 2) }}</p>
                                <h2 class="h3">{{ $type->name }}</h2>
                                <p class="text-muted">{{ Illuminate\Support\Str::limit($type->description ?: 'Comfortable accommodation designed for a relaxing stay.', 120) }}</p>
                                <div class="d-flex gap-3 text-muted small mb-4"><span><i class="bi bi-people me-1"></i>{{ $type->capacity }} guests</span><span><i class="bi bi-moon-stars me-1"></i>{{ $type->bed_type ?: 'Flexible bed' }}</span></div>
                                <a href="{{ route('room-types.show', $type) }}" class="btn btn-outline-primary mt-auto">View rooms</a>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12"><x-empty-state icon="bi-house-door" title="No room types available" message="Please check back soon." /></div>
                @endforelse
            </div>
        @endif

        @if($roomTypes->hasPages())<div class="mt-4">{{ $roomTypes->links('pagination::bootstrap-5') }}</div>@endif
    </div>
</section>
@endsection
