@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>Resort Membership</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($currentMembership && $currentMembership->isActive())
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">You're a {{ $currentMembership->membershipType->name }} member</h5>
                <p class="card-text">
                    {{ $currentMembership->discountPercentage() }}% discount on bookings.<br>
                    Valid until {{ $currentMembership->end_date->format('M d, Y') }}.
                </p>
                <form action="{{ route('memberships.cancel', $currentMembership) }}" method="POST"
                      onsubmit="return confirm('Cancel your membership?');">
                    @csrf
                    <button class="btn btn-outline-danger btn-sm">Cancel Membership</button>
                </form>
            </div>
        </div>
    @else
        <p class="text-muted">You don't have an active membership yet. Choose a tier below:</p>

        <div class="row g-3">
            @foreach ($membershipTypes as $type)
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $type->name }}</h5>
                            <p class="card-text">{{ $type->description }}</p>
                            <p class="fw-bold">{{ $type->discount_percentage }}% off bookings</p>
                            <p class="text-muted small">{{ $type->duration_months }} months</p>
                            <form action="{{ route('memberships.subscribe') }}" method="POST" class="mt-auto">
                                @csrf
                                <input type="hidden" name="membership_type_id" value="{{ $type->id }}">
                                <button class="btn btn-primary w-100">Join {{ $type->name }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
