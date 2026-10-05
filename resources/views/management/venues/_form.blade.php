@php
    $editing = isset($venue);
    $selectedEventTypes = old('event_types', $editing ? $venue->event_types : []);
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <p class="fw-semibold mb-2">Please correct the following:</p>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-7">
        <label for="name" class="form-label">Venue name</label>
        <input id="name" type="text" name="name" maxlength="255" required
               value="{{ old('name', $venue->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror">
    </div>

    <div class="col-md-5">
        <label for="location" class="form-label">Location</label>
        <input id="location" type="text" name="location" maxlength="255" required
               value="{{ old('location', $venue->location ?? '') }}"
               class="form-control @error('location') is-invalid @enderror"
               placeholder="e.g. Ground Floor, East Wing">
    </div>

    <div class="col-md-6">
        <label for="capacity" class="form-label">Maximum capacity</label>
        <input id="capacity" type="number" name="capacity" min="1" max="100000" required
               value="{{ old('capacity', $venue->capacity ?? '') }}"
               class="form-control @error('capacity') is-invalid @enderror">
    </div>

    <div class="col-md-6">
        <label for="price" class="form-label">Venue price <span class="text-muted">(optional)</span></label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input id="price" type="number" name="price" min="0" max="99999999.99" step="0.01"
                   value="{{ old('price', $venue->price ?? '') }}"
                   class="form-control @error('price') is-invalid @enderror">
        </div>
    </div>

    <div class="col-12">
        <label class="form-label d-block">Supported event types</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach($eventTypes as $eventType)
                <div class="form-check">
                    <input id="event_type_{{ $eventType }}" type="checkbox" name="event_types[]"
                           value="{{ $eventType }}" class="form-check-input"
                           @checked(in_array($eventType, $selectedEventTypes, true))>
                    <label for="event_type_{{ $eventType }}" class="form-check-label">{{ ucfirst($eventType) }}</label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-12">
        <label for="description" class="form-label">Description</label>
        <textarea id="description" name="description" rows="5" maxlength="5000"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $venue->description ?? '') }}</textarea>
    </div>

    @if($editing && ! empty($venue->images))
        <div class="col-12">
            <label class="form-label d-block">Current images</label>
            <div class="row g-3">
                @foreach($venue->images as $image)
                    <div class="col-6 col-md-4">
                        <div class="border rounded p-2">
                            <img src="{{ asset('storage/'.$image) }}" class="img-fluid rounded object-fit-cover w-100"
                                 style="height: 130px;" alt="{{ $venue->name }} image">
                            <div class="form-check mt-2">
                                <input id="remove_{{ $loop->index }}" type="checkbox" name="remove_images[]"
                                       value="{{ $image }}" class="form-check-input"
                                       @checked(in_array($image, old('remove_images', []), true))>
                                <label for="remove_{{ $loop->index }}" class="form-check-label text-danger">Remove</label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="col-12">
        <label for="images" class="form-label">{{ $editing ? 'Add images' : 'Venue images' }} <span class="text-muted">(up to 5 total)</span></label>
        <input id="images" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp"
               class="form-control @error('images') is-invalid @enderror @error('images.*') is-invalid @enderror"
               data-preview-target="venue-image-preview">
        <div class="form-text">JPG, PNG, or WebP. Maximum 5 MB per image.</div>
        <div id="venue-image-preview" class="image-preview-grid"></div>
    </div>

    @if($editing)
        <div class="col-12">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch">
                <input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input"
                       @checked((bool) old('is_active', $venue->is_active))>
                <label for="is_active" class="form-check-label">Venue is active and visible to customers</label>
            </div>
        </div>
    @endif
</div>

<div class="d-flex justify-content-between mt-4">
    <a href="{{ $editing ? route('management.venues.show', $venue) : route('management.venues.index') }}"
       class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary">{{ $editing ? 'Save Changes' : 'Create Venue' }}</button>
</div>
