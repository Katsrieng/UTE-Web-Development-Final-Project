<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Types</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    @include('partials.navbar')

    <div class="container py-4" style="max-width: 1000px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Room Types</h2>
            <div>
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary me-2">View Rooms</a>
                <a href="{{ route('room-types.create') }}" class="btn btn-primary">+ Add Room Type</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Photo</th>
                            <th>Name</th>
                            <th>Base Price</th>
                            <th>Capacity</th>
                            <th>Bed Type</th>
                            <th>Description</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roomTypes as $type)
                            <tr>
                                <td class="ps-4">
                                    @if(!empty($type->image))
                                        <img src="{{ $type->image }}" 
                                             alt="{{ $type->name }}" 
                                             class="rounded shadow-sm" 
                                             style="width: 60px; height: 42px; object-fit: cover;">
                                    @else
                                        <div class="bg-secondary-subtle text-secondary rounded d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 42px; font-size: 11px;">
                                            No photo
                                        </div>
                                    @endif
                                </td>
                                <td><strong>{{ $type->name }}</strong></td>
                                <td>${{ number_format($type->base_price, 2) }}</td>
                                <td>{{ $type->capacity }} Guests</td>
                                <td>{{ $type->bed_type ?? '—' }}</td>
                                <td>{{ $type->description ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('room-types.edit', $type->id) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                    <form action="{{ route('room-types.destroy', $type->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this room type?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No room types found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if(method_exists($roomTypes, 'links'))
                    <div class="p-3 border-top">
                        {{ $roomTypes->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>