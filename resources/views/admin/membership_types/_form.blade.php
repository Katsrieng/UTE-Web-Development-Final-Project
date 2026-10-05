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
           value="{{ old('name', $membershipType->name ?? '') }}" required>
</div>

<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3">{{ old('description', $membershipType->description ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Discount Percentage</label>
        <input type="number" step="0.01" min="0" max="100" name="discount_percentage" class="form-control"
               value="{{ old('discount_percentage', $membershipType->discount_percentage ?? 0) }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Duration (months)</label>
        <input type="number" min="1" name="duration_months" class="form-control"
               value="{{ old('duration_months', $membershipType->duration_months ?? 12) }}" required>
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
        @php $status = old('status', $membershipType->status ?? 'active'); @endphp
        <option value="active" @selected($status === 'active')>Active</option>
        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
    </select>
</div>
