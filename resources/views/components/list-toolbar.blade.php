@props(['action', 'placeholder' => 'Search', 'fields' => []])
<form method="GET" action="{{ $action }}" class="list-toolbar mb-3" role="search" aria-label="Search and filter this list">
<div class="row g-2 align-items-end">
<div class="col-12 col-md-6 col-xl"><label class="form-label small mb-1" for="list-search">Search</label><input id="list-search" type="search" name="search" maxlength="100" value="{{ is_scalar(request('search')) ? request('search') : '' }}" placeholder="{{ $placeholder }}" class="form-control"></div>
@foreach($fields as $name => $field)
<div class="col-12 col-sm-6 col-xl"><label class="form-label small mb-1" for="list-{{ $name }}">{{ $field['label'] }}</label>
@if(isset($field['options']))
<select id="list-{{ $name }}" name="{{ $name }}" class="form-select"><option value="">{{ $field['all'] ?? 'All' }}</option>@foreach($field['options'] as $value => $label)<option value="{{ $value }}" @selected(is_scalar(request($name)) && (string)request($name) === (string)$value)>{{ $label }}</option>@endforeach</select>
@else
<input id="list-{{ $name }}" name="{{ $name }}" type="{{ $field['type'] ?? 'text' }}" value="{{ is_scalar(request($name)) ? request($name) : '' }}" @if(isset($field['min'])) min="{{ $field['min'] }}" @endif @if(isset($field['step'])) step="{{ $field['step'] }}" @endif class="form-control">
@endif
</div>
@endforeach
<div class="col-12 col-md-auto d-flex gap-2"><button type="submit" class="btn btn-hotel">Apply</button><a class="btn btn-outline-secondary" href="{{ $action }}">Clear</a></div>
</div>
@if($errors->any())<div class="small text-danger mt-2" role="alert">{{ $errors->first() }}</div>@endif
</form>
