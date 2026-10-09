@extends('layouts.management')

@section('title', 'Customers')
@section('page-label', 'Customers')

@section('content')
<div class="page-heading">
    <div>
        <p class="section-kicker">Customer Management</p>
        <h1>Customers</h1>
        <p>View and manage customer accounts and activity.</p>
    </div>
</div>

<x-list-toolbar :action="route('management.customers.index')" placeholder="Name, email or phone" :fields="['active'=>['label'=>'Account status','all'=>'All customers','options'=>['1'=>'Active','0'=>'Inactive']]]" />

<div class="table-card">
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead><tr><th class="ps-4">ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Bookings</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td class="ps-4">#{{ $customer->id }}</td>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone ?? '—' }}</td>
                    <td><x-status-badge :status="$customer->is_active ? 'active' : 'disabled'" /></td>
                    <td>{{ $customer->bookings_count }}</td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('management.customers.show', $customer) }}" aria-label="View {{ $customer->name }}"><i class="bi bi-eye"></i></a>
                        @staffroute('management.customers.edit')
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('management.customers.edit', $customer) }}" aria-label="Edit {{ $customer->name }}"><i class="bi bi-pencil"></i></a>
                        @endstaffroute
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-list-no-results module="customers" :clear="route('management.customers.index')" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-3 pt-3">{{ $customers->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
