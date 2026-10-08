@extends('layouts.management')
@section('title', 'Roles & Permissions')
@section('page-label', 'Administration')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Administration</p><h1 class="h3">Roles &amp; Permissions</h1><p>Control what each staff role can access and manage.</p></div></div>
<div class="row g-3"><div class="col-lg-3"><nav class="rbac-role-list" aria-label="Staff roles">
@foreach($roles as $role)<a href="{{ route('admin.roles.index',['role'=>$role->slug]) }}" class="rbac-role {{ $selected?->id === $role->id ? 'is-selected' : '' }}" @if($selected?->id === $role->id) aria-current="page" @endif><strong>{{ $role->name }}</strong><small>{{ $role->description }}</small><span>{{ $role->slug === 'admin' ? $groups->flatten()->count() : $role->permissions_count }} permissions</span></a>@endforeach
</nav></div><div class="col-lg-9">
@if(!$selected)<div class="alert alert-info">Run the role/permission seeder to initialize staff access.</div>
@else
<form method="POST" action="{{ route('admin.roles.update',$selected) }}">@csrf @method('PATCH')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h2 class="h5 mb-1">{{ $selected->name }}</h2><small class="text-muted">Changes apply to everyone with this role.</small></div>@if(!$selected->is_protected)<button class="btn btn-hotel" type="submit">Save Permissions</button>@endif</div>
@if($selected->is_protected)<p class="rbac-protected"><i class="bi bi-shield-lock me-2"></i>Administrator access is protected.</p>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<div class="rbac-permission-columns">
@foreach($groups->chunk((int) ceil($groups->count() / 2)) as $column)
<div class="rbac-permission-column">
@foreach($column as $group=>$permissions)
<section class="rbac-group"><h3>{{ $group }}</h3>
@foreach($permissions as $permission)
@php($adminReserved = in_array($permission->slug,$adminOnly,true))
<div class="rbac-permission"><div><label for="permission-{{ $permission->id }}">{{ $permission->name }}</label><small>{{ $adminReserved ? 'Reserved for Admin.' : $permission->description }}</small></div><div class="form-check form-switch m-0"><input id="permission-{{ $permission->id }}" type="checkbox" name="permissions[]" value="{{ $permission->slug }}" class="form-check-input" @checked($selected->is_protected || in_array($permission->slug,(is_array(old('permissions')) ? old('permissions') : $selected->permissions->pluck('slug')->all()),true)) @disabled($selected->is_protected || $adminReserved) aria-label="{{ $permission->name }}"></div></div>
@endforeach</section>
@endforeach
</div>
@endforeach
</div>
@if(!$selected->is_protected)<div class="d-flex justify-content-end mt-3"><button class="btn btn-hotel" type="submit">Save Permissions</button></div>@endif
</form>@endif
</div></div>
@endsection
