<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Room</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add New Room</h4>
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

                <form action="{{ route('rooms.store') }}" method="POST">
                    @csrf

                    <!-- Room Number -->
                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" name="room_number" class="form-control" placeholder="e.g. 101, 202" value="{{ old('room_number', '101') }}" required>
                    </div>

                    <!-- Room Type Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <select name="room_type_id" class="form-select" required>
                            <option value="">-- Select Room Type --</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}" {{ old('room_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }} (${{ number_format($type->base_price, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Floor -->
                    <div class="mb-3">
                        <label class="form-label">Floor</label>
                        <input type="number" name="floor" class="form-control" placeholder="e.g. 1" value="{{ old('floor', 1) }}" required>
                    </div>

                    <!-- Price Per Night -->
                    <div class="mb-3">
                        <label class="form-label">Price Per Night ($)</label>
                        <input type="number" step="0.01" name="price_per_night" class="form-control" placeholder="e.g. 75.00" value="{{ old('price_per_night', '75.00') }}" required>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="available" selected>Available</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <!-- Facilities Checkboxes -->
                    @if(isset($facilities) && $facilities->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label d-block">Room Facilities</label>
                            <div class="row">
                                @foreach($facilities as $facility)
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="facilities[]" value="{{ $facility->id }}" id="facility_{{ $facility->id }}">
                                            <label class="form-check-label" for="facility_{{ $facility->id }}">
                                                {{ $facility->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between pt-2">
                        <a href="{{ route('rooms.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>