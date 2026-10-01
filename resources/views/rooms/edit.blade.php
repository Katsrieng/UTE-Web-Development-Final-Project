<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room {{ $room->room_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 600px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Edit Room {{ $room->room_number }}</h4>
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

                <form action="{{ route('rooms.update', $room->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Room Number -->
                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" name="room_number" class="form-control" value="{{ old('room_number', $room->room_number) }}" required>
                    </div>

                    <!-- Room Type Dropdown -->
                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <select name="room_type_id" class="form-select" required>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}" {{ (string)old('room_type_id', $room->room_type_id) === (string)$type->id ? 'selected' : '' }}>
                                    {{ $type->name }} (${{ number_format($type->base_price, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Floor -->
                    <div class="mb-3">
                        <label class="form-label">Floor</label>
                        <input type="number" min="1" name="floor" class="form-control" value="{{ old('floor', $room->floor) }}" required>
                    </div>

                    <!-- Price Per Night -->
                    <div class="mb-3">
                        <label class="form-label">Price Per Night ($)</label>
                        <input type="number" step="0.01" min="0" name="price_per_night" class="form-control" value="{{ old('price_per_night', $room->price_per_night) }}" required>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="available" {{ old('status', $room->status) === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="booked" {{ old('status', $room->status) === 'booked' ? 'selected' : '' }}>Booked</option>
                            <option value="occupied" {{ old('status', $room->status) === 'occupied' ? 'selected' : '' }}>Occupied</option>
                            <option value="maintenance" {{ old('status', $room->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="cleaning" {{ old('status', $room->status) === 'cleaning' ? 'selected' : '' }}>Cleaning</option>
                        </select>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $room->description) }}</textarea>
                    </div>

                    <!-- Facilities Checkboxes -->
                    @if(isset($facilities) && $facilities->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label d-block">Facilities</label>
                            <div class="row">
                                @foreach($facilities as $facility)
                                    <div class="col-md-6 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="facilities[]" value="{{ $facility->id }}" id="facility_{{ $facility->id }}"
                                                {{ (is_array(old('facilities')) && in_array($facility->id, old('facilities'))) || ($room->facilities && $room->facilities->contains($facility->id)) ? 'checked' : '' }}>
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
                        <button type="submit" class="btn btn-primary">Update Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>