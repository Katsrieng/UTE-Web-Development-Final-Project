<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facilities & Amenities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    @include('partials.navbar')

    <div class="container py-4" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Facilities & Amenities</h2>
            <div>
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary me-2">Back to Rooms</a>
                <a href="{{ route('facilities.create') }}" class="btn btn-primary">+ Add Facility</a>
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
                            <th class="ps-4">Facility Name</th>
                            <th>Description</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($facilities as $facility)
                            <tr>
                                <td class="ps-4">
                                    <strong>{{ $facility->name }}</strong>
                                </td>
                                <td>{{ $facility->description ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('facilities.edit', $facility->id) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                    <form action="{{ route('facilities.destroy', $facility->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this facility?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    No facilities found. Click "+ Add Facility" to register amenities.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>