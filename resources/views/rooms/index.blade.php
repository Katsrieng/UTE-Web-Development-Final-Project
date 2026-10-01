<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-white">
    <div class="container-fluid" style="max-width: 1200px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">Rooms Management</h2>
            <div class="d-flex gap-2">
                <a href="{{ route('facilities.index') }}" class="btn btn-outline-secondary">Facilities</a>
                <a href="{{ route('room-types.index') }}" class="btn btn-outline-secondary">Room Types</a>
                <a href="{{ route('rooms.create') }}" class="btn btn-primary">+ Add New Room</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card border rounded-0 shadow-none">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3 py-3">Room</th>
                            <th class="py-3">Type</th>
                            <th class="py-3">Floor</th>
                            <th class="py-3">Price / Night</th>
                            <th class="py-3">Facilities</th>
                            <th class="py-3">Status</th>
                            <th class="text-end pe-3 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td class="ps-3 fw-bold">Room {{ $room->room_number }}</td>
                                <td>{{ $room->roomType->name ?? '—' }}</td>
                                <td>Floor {{ $room->floor }}</td>
                                <td>${{ number_format($room->price_per_night, 2) }}</td>
                                <td>
                                    @if($room->facilities->isNotEmpty())
                                        <span class="badge bg-secondary fw-normal px-2 py-1">
                                            {{ $room->facilities->pluck('name')->join(', ') }}
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badgeBg = match(strtolower($room->status)) {
                                            'available' => 'bg-success',
                                            'maintenance' => 'bg-warning text-dark',
                                            default => 'bg-danger'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeBg }} px-2 py-1 text-capitalize">
                                        {{ $room->status }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('rooms.edit', $room->id) }}" class="btn btn-sm btn-outline-primary px-3 py-1">Edit</a>
                                    <form action="{{ route('rooms.destroy', $room->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete Room {{ $room->room_number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No rooms found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if(method_exists($rooms, 'links'))
                <div class="p-3 border-top">
                    {{ $rooms->links() }}
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>