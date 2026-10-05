@php
    $editing = isset($roomType);
    $roomTypeImage = $editing && $roomType->image
        ? (str_starts_with($roomType->image, '/storage/') ? asset(ltrim($roomType->image, '/')) : asset('storage/'.$roomType->image))
        : null;
@endphp

@if($errors->any())
    <div class="validation-summary mb-4">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div><strong>Please correct the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-3">
                    <div class="col-md-7"><label for="name" class="form-label">Type name</label><input id="name" type="text" name="name" value="{{ old('name', $roomType->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Deluxe Suite" required></div>
                    <div class="col-md-5"><label for="bed_type" class="form-label">Bed type</label><input id="bed_type" type="text" name="bed_type" value="{{ old('bed_type', $roomType->bed_type ?? '') }}" class="form-control" placeholder="e.g. 1 King Bed"></div>
                    <div class="col-md-6"><label for="base_price" class="form-label">Base price</label><div class="input-group"><span class="input-group-text">$</span><input id="base_price" type="number" min="0" step="0.01" name="base_price" value="{{ old('base_price', $roomType->base_price ?? '') }}" class="form-control" required></div></div>
                    <div class="col-md-6"><label for="capacity" class="form-label">Capacity</label><div class="input-group"><input id="capacity" type="number" min="1" name="capacity" value="{{ old('capacity', $roomType->capacity ?? '') }}" class="form-control" required><span class="input-group-text">guests</span></div></div>
                    <div class="col-12"><label for="description" class="form-label">Description</label><textarea id="description" name="description" class="form-control" rows="5">{{ old('description', $roomType->description ?? '') }}</textarea></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Room type image</h2>
                @if($roomTypeImage)<img src="{{ $roomTypeImage }}" alt="{{ $roomType->name }}" class="img-fluid rounded mb-3 w-100" style="height:180px;object-fit:cover">@endif
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror" data-preview-target="room-type-image-preview">
                <div class="form-text">JPG, PNG, or WebP. Maximum 2 MB.</div>
                <div id="room-type-image-preview" class="image-preview-grid"></div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between mt-4"><a href="{{ route('management.room-types.index') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-hotel" type="submit">{{ $editing ? 'Save Changes' : 'Create Room Type' }}</button></div>
