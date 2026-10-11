@extends(request()->routeIs('management.facilities.*') ? 'layouts.management' : 'layouts.app')
@section('title', 'Facilities & Amenities')
@section('page-label', 'Facilities')
@section('content')
@php($managing = request()->routeIs('management.facilities.*'))
@unless($managing)
    <section class="page-hero"><div class="container"><p class="section-kicker">More than a stay</p><h1>Facilities designed around you</h1><p>Discover the amenities and services that make every hotel visit more comfortable.</p></div></section>
@endunless
<section class="{{ $managing ? '' : 'content-section' }}"><div class="{{ $managing ? '' : 'container' }}">
    <div class="page-heading"><div>
        @if($managing)<p class="section-kicker">Guest experience</p>@endif
        <h1>{{ $managing ? 'Manage Facilities' : 'Facilities & Amenities' }}</h1>
        <p>{{ $managing ? 'Maintain the amenities available across hotel rooms.' : 'Thoughtful details for a more enjoyable stay.' }}</p>
    </div>
    @if($managing)<div class="d-flex gap-2">
        @staffroute('management.rooms.index')<a href="{{ route('management.rooms.index') }}" class="btn btn-outline-secondary">Rooms</a>@endstaffroute
        @staffroute('management.facilities.create')<a href="{{ route('management.facilities.create') }}" class="btn btn-hotel"><i class="bi bi-plus-lg me-1"></i> Add Facility</a>@endstaffroute
    </div>@endif</div>

    @if($managing)
        <div class="table-card"><div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th class="ps-4">Facility</th><th>Description</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead>
            <tbody>
            @forelse($facilities as $facility)
                <tr>
                    <td class="ps-4"><div class="d-flex align-items-center gap-3">
                        @if($facility->imageUrl())<img class="facility-table-photo" src="{{ $facility->imageUrl() }}" alt="" loading="lazy">
                        @else<div class="facility-table-photo facility-photo-placeholder"><i class="bi bi-image" aria-hidden="true"></i></div>@endif
                        <strong>{{ $facility->name }}</strong>
                    </div></td>
                    <td class="text-muted">{{ Illuminate\Support\Str::limit($facility->description ?: 'No description provided.', 110) }}</td>
                    <td><x-status-badge :status="$facility->status" context="facility" /></td>
                    <td class="text-end pe-4 text-nowrap">
                        @staffroute('management.facilities.show')<a href="{{ route('management.facilities.show', $facility) }}" class="btn btn-sm btn-outline-primary" aria-label="View {{ $facility->name }}"><i class="bi bi-eye"></i></a>@endstaffroute
                        @staffroute('management.facilities.edit')<a href="{{ route('management.facilities.edit', $facility) }}" class="btn btn-sm btn-outline-secondary" aria-label="Edit {{ $facility->name }}"><i class="bi bi-pencil"></i></a>@endstaffroute
                        @staffroute('management.facilities.destroy')<form action="{{ route('management.facilities.destroy', $facility) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $facility->name }}" data-confirm="Delete {{ $facility->name }}?"><i class="bi bi-trash"></i></button></form>@endstaffroute
                    </td>
                </tr>
            @empty
                <tr><td colspan="4"><x-empty-state icon="bi-stars" title="No facilities found" message="Add an amenity to start building the hotel facility list." /></td></tr>
            @endforelse
            </tbody>
        </table></div></div>
    @else
        <div class="row g-4">
            @forelse($facilities as $facility)
                <div class="col-md-6 col-lg-4"><article class="hotel-card facility-card">
                    @if($facility->imageUrl())<img class="facility-card-photo" src="{{ $facility->imageUrl() }}" alt="{{ $facility->name }}" loading="lazy">
                    @else<div class="facility-card-photo facility-photo-placeholder"><i class="bi bi-stars" aria-hidden="true"></i></div>@endif
                    <div class="facility-card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2"><h2 class="h4 mb-2">{{ $facility->name }}</h2><x-status-badge :status="$facility->status" context="facility" /></div>
                        <p>{{ $facility->description ?: 'Available to support a comfortable hotel experience.' }}</p>
                        <a href="{{ route('facilities.show', $facility) }}" class="btn btn-sm btn-outline-primary mt-auto align-self-start">Learn more</a>
                    </div>
                </article></div>
            @empty
                <div class="col-12"><x-empty-state icon="bi-stars" title="Facilities coming soon" message="Our facility catalogue is being prepared." /></div>
            @endforelse
        </div>
    @endif
    @if($facilities->hasPages())<div class="mt-4">{{ $facilities->links('pagination::bootstrap-5') }}</div>@endif
</div></section>
@endsection
