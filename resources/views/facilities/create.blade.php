<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Facility</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add New Facility</h4>
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

                <form action="{{ route('facilities.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Facility Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. High-Speed Wi-Fi" value="{{ old('name') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief note about the facility...">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="1" {{ old('status', '1') == '1' ? 'selected' : '' }}>Active / Available</option>
                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Inactive / Unavailable</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between pt-2">
                        <a href="{{ route('facilities.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Facility</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>