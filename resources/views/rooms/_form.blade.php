@php($editing = isset($room))

@if($errors->any())
    <div class="validation-summary mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please correct the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif

<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">Room information</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label for="room_number" class="form-label">Room number</label><input id="room_number" type="text" name="room_number" value="{{ old('room_number', $room->room_number ?? '') }}" class="form-control @error('room_number') is-invalid @enderror" required></div>
                    <div class="col-md-6"><label for="room_type_id" class="form-label">Room type</label><select id="room_type_id" name="room_type_id" class="form-select @error('room_type_id') is-invalid @enderror" required><option value="">Select a room type</option>@foreach($roomTypes as $type)<option value="{{ $type->id }}" @selected((int) old('room_type_id', $room->room_type_id ?? 0) === $type->id)>{{ $type->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label for="floor" class="form-label">Floor</label><input id="floor" type="number" min="1" name="floor" value="{{ old('floor', $room->floor ?? '') }}" class="form-control @error('floor') is-invalid @enderror" required></div>
                    <div class="col-md-4"><label for="price_per_night" class="form-label">Price per night</label><div class="input-group"><span class="input-group-text">$</span><input id="price_per_night" type="number" min="0" step="0.01" name="price_per_night" value="{{ old('price_per_night', $room->price_per_night ?? '') }}" class="form-control @error('price_per_night') is-invalid @enderror" required></div></div>
                    <div class="col-md-4"><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-select" required>@foreach(['available','booked','occupied','maintenance','cleaning'] as $status)<option value="{{ $status }}" @selected(old('status', $room->status ?? 'available') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
                    <div class="col-12"><label for="description" class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label><textarea id="description" name="description" class="form-control" rows="4">{{ old('description', $room->description ?? '') }}</textarea></div>
                </div>
            </div>
        </div>
    </div>
</div>
