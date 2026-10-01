@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control"
           value="{{ old('name', $package->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Type</label>
    @php $type = old('type', $package->type ?? 'accommodation'); @endphp
    <select name="type" class="form-select">
        <option value="accommodation" @selected($type === 'accommodation')>Accommodation</option>
        <option value="buffet" @selected($type === 'buffet')>Buffet</option>
        <option value="romantic" @selected($type === 'romantic')>Romantic</option>
        <option value="family" @selected($type === 'family')>Family</option>
        <option value="other" @selected($type === 'other')>Other</option>
    </select>
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $package->description ?? '') }}</textarea>
</div>

<div class="mb-3">
    <label class="form-label">Price</label>
    <input type="number" step="0.01" min="0" name="price" class="form-control"
           value="{{ old('price', $package->price ?? 0) }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Status</label>
    @php $status = old('status', $package->status ?? 'active'); @endphp
    <select name="status" class="form-select">
        <option value="active" @selected($status === 'active')>Active</option>
        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
    </select>
</div>
