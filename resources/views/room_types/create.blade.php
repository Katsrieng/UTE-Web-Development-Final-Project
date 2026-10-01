<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Room Type</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add New Room Type</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('room-types.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Type Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Deluxe Suite" value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Base Price ($)</label>
                        <input type="number" step="0.01" name="base_price" class="form-control" placeholder="e.g. 75.00" value="{{ old('base_price') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Capacity (Persons)</label>
                        <input type="number" name="capacity" class="form-control" placeholder="e.g. 2" value="{{ old('capacity') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bed Type</label>
                        <input type="text" name="bed_type" class="form-control" placeholder="e.g. 1 King Bed" value="{{ old('bed_type') }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Short description...">{{ old('description') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between pt-2">
                        <a href="{{ route('rooms.create') }}" class="btn btn-secondary">Back to Add Room</a>
                        <button type="submit" class="btn btn-primary">Save Room Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
