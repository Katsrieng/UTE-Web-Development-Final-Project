@extends('layouts.management')

@section('title', 'Customer Account')
@section('page-label', 'Customers')

@section('content')
<div class="page-heading">
    <div><p class="section-kicker">Customer Management</p><h1>{{ $customer->name }}</h1><p>Customer account #{{ $customer->id }}</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('management.customers.index') }}">Back to Customers</a>
</div>
<div class="row g-3">
    <div class="col-lg-8"><div class="card shadow-sm h-100"><div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3"><h2 class="h5 mb-0">Account details</h2><x-status-badge :status="$customer->is_active ? 'active' : 'disabled'" /></div>
        <dl class="row mb-0"><dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $customer->email }}</dd><dt class="col-sm-4">Phone</dt><dd class="col-sm-8">{{ $customer->phone ?? '—' }}</dd><dt class="col-sm-4">Joined</dt><dd class="col-sm-8">{{ $customer->created_at->format('M j, Y') }}</dd></dl>
        @staffroute('management.customers.edit')<a class="btn btn-hotel btn-sm" href="{{ route('management.customers.edit', $customer) }}">Edit account</a>@endstaffroute
    </div></div></div>
    <div class="col-lg-4"><div class="card shadow-sm h-100"><div class="card-body">
        <h2 class="h5">Activity</h2>
        <div class="d-flex justify-content-between border-bottom py-2"><span>Bookings</span><strong>{{ $customer->bookings_count }}</strong></div>
        <div class="d-flex justify-content-between border-bottom py-2"><span>Payments</span><strong>{{ $customer->payments_count }}</strong></div>
        <div class="d-flex justify-content-between py-2"><span>Event reservations</span><strong>{{ $customer->event_bookings_count }}</strong></div>
    </div></div></div>
</div>
@endsection
