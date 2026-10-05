@php($editing = isset($room))
@php($currentRoomImage = $editing && $room->image ? (str_starts_with($room->image, 'images/') ? asset($room->image) : asset('storage/'.$room->image)) : null)

@if($errors->any())
    <div class="validation-summary mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please correct the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
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
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Room image</h2>
                @if($currentRoomImage)<img src="{{ $currentRoomImage }}" alt="Room {{ $room->room_number }}" class="img-fluid rounded mb-3 w-100" style="height:180px;object-fit:cover">@endif
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror" data-preview-target="room-image-preview">
                <div class="form-text">JPG, PNG, or WebP. Maximum 2 MB.</div>
                <div id="room-image-preview" class="image-preview-grid"></div>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Facilities</h2>
                @forelse($facilities as $facility)
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="facilities[]" value="{{ $facility->id }}" id="facility_{{ $facility->id }}" @checked(in_array($facility->id, old('facilities', $editing ? $room->facilities->pluck('id')->all() : [])))><label class="form-check-label" for="facility_{{ $facility->id }}">{{ $facility->name }}</label></div>
                @empty
                    <p class="text-muted small mb-0">No facilities are available yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-4"><a href="{{ route('management.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-hotel">{{ $editing ? 'Save Changes' : 'Create Room' }}</button></div>
