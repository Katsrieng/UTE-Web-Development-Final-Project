@php($editing = isset($facility))
@if($errors->any())
    <div class="validation-summary mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please correct the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
@endif
<div class="card shadow-sm border-0"><div class="card-body p-4 p-lg-5"><div class="row g-3">
    <div class="col-md-8"><label for="name" class="form-label">Facility name</label><input id="name" type="text" name="name" value="{{ old('name', $facility->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. High-Speed Wi-Fi" required></div>
    <div class="col-md-4"><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-select" required><option value="open" @selected(old('status', $facility->status ?? 'open') === 'open')>Open</option><option value="closed" @selected(old('status', $facility->status ?? 'open') === 'closed')>Closed</option><option value="maintenance" @selected(old('status', $facility->status ?? 'open') === 'maintenance')>Maintenance</option></select></div>
    <div class="col-12"><label for="description" class="form-label">Description</label><textarea id="description" name="description" class="form-control" rows="5" placeholder="Explain what guests can expect from this facility.">{{ old('description', $facility->description ?? '') }}</textarea></div>
    <div class="col-12"><label for="image" class="form-label">Facility photo</label><div class="facility-photo-form-row">
        @if($editing && $facility->imageUrl())
            <img class="facility-photo-form-preview" src="{{ $facility->imageUrl() }}" alt="Current photo of {{ $facility->name }}">
        @else
            <div class="facility-photo-form-preview facility-photo-placeholder" aria-label="No facility photo"><i class="bi bi-image" aria-hidden="true"></i></div>
        @endif
        <div class="flex-grow-1"><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror" aria-describedby="facility-image-help"><div id="facility-image-help" class="form-text">JPG, PNG or WebP · Max 2 MB. {{ $editing ? 'Leave empty to keep the current photo.' : 'Optional.' }}</div>@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    </div></div>
</div></div></div>
<div class="d-flex justify-content-between mt-4">
@staffroute('management.facilities.index')
<a href="{{ route('management.facilities.index') }}" class="btn btn-outline-secondary">Cancel</a>
@endstaffroute
<button type="submit" class="btn btn-hotel">{{ $editing ? 'Save Changes' : 'Create Facility' }}</button></div>
