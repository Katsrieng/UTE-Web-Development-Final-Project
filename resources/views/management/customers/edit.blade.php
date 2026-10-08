@extends('layouts.management')

@section('title', 'Edit Customer')
@section('page-label', 'Customers')

@section('content')
<div class="page-heading"><div><p class="section-kicker">Customer Management</p><h1>Edit Customer</h1><p>{{ $customer->name }} · #{{ $customer->id }}</p></div></div>
<div class="row g-3">
    <div class="col-lg-8"><div class="card shadow-sm"><div class="card-body">
        <form method="POST" action="{{ route('management.customers.update', $customer) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6"><label for="name" class="form-label">Full name</label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $customer->name) }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label for="email" class="form-label">Email</label><input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label for="phone" class="form-label">Phone</label><input id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $customer->phone) }}">@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><input type="hidden" name="is_active" value="0"><div class="form-check"><input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input" @checked((bool) old('is_active', $customer->is_active))><label for="is_active" class="form-check-label">Active account</label></div><small class="text-muted">Deactivate an account to prevent login while retaining its history.</small></div>
            </div>
            <div class="d-flex gap-2 mt-4"><button class="btn btn-hotel" type="submit">Save customer</button><a class="btn btn-outline-secondary" href="{{ route('management.customers.show', $customer) }}">Cancel</a></div>
        </form>
    </div></div></div>
    @staffroute('management.customers.destroy')
    <div class="col-lg-4"><div class="card shadow-sm"><div class="card-body"><h2 class="h6">Delete account</h2><p class="small text-muted">Only accounts without booking, payment, membership, loyalty, or event history can be deleted.</p>
        <form method="POST" action="{{ route('management.customers.destroy', $customer) }}">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Delete {{ $customer->name }}? This cannot be undone.">Delete customer</button></form>
    </div></div></div>
    @endstaffroute
</div>
@endsection
