<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room {{ $room->room_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 650px;">
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

                <form action="{{ route('rooms.update', $room) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Room Number</label>
                        <input type="text" name="room_number" class="form-control" value="{{ old('room_number', $room->room_number) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Room Type</label>
                        <select name="room_type_id" class="form-select" required>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}" {{ old('room_type_id', $room->room_type_id) == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }} (${{ number_format($type->base_price, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Floor</label>
                            <input type="number" name="floor" class="form-control" value="{{ old('floor', $room->floor) }}" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price Per Night ($)</label>
                            <input type="number" step="0.01" name="price_per_night" class="form-control" value="{{ old('price_per_night', $room->price_per_night) }}" min="0" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach(['available' => 'Available', 'booked' => 'Booked', 'occupied' => 'Occupied', 'maintenance' => 'Maintenance', 'cleaning' => 'Cleaning'] as $key => $label)
                                <option value="{{ $key }}" {{ old('status', $room->status) == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Facilities</label>
                        <div class="d-flex flex-wrap gap-3 p-2 border rounded bg-white">
                            @foreach($facilities as $facility)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="{{ $facility->id }}" id="facility_{{ $facility->id }}"
                                        {{ (is_array(old('facilities')) && in_array($facility->id, old('facilities'))) || (!old('facilities') && $room->facilities->contains($facility->id)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="facility_{{ $facility->id }}">
                                        {{ $facility->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $room->description) }}</textarea>
                    </div>

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