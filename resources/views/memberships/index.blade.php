@extends('layouts.app')
@section('title', 'Membership')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">More value, every visit</p><h1>Hotel membership</h1><p>Enjoy member recognition and savings designed for guests who return.</p></div></section>
<section class="content-section"><div class="container">
    @if($currentMembership && $currentMembership->isActive())
        <div class="row justify-content-center"><div class="col-xl-9"><div class="hotel-card p-4 p-lg-5"><div class="row align-items-center g-4"><div class="col-md-auto"><span class="empty-state-icon m-0" style="width:100px;height:100px;font-size:2.4rem"><i class="bi bi-award"></i></span></div><div class="col"><p class="section-kicker">Active membership</p><div class="d-flex flex-wrap justify-content-between gap-3 align-items-start"><h1 class="section-title mb-2">{{ $currentMembership->membershipType->name }} Member</h1><x-status-badge status="active" /></div><p class="section-copy">Enjoy {{ $currentMembership->discountPercentage() }}% off eligible bookings through {{ $currentMembership->end_date->format('F j, Y') }}.</p>
            <div class="row g-3 mt-1 mb-2">
                <div class="col-sm-4"><small class="text-muted d-block">Member since</small><strong>{{ $currentMembership->start_date->format('M j, Y') }}</strong></div>
                <div class="col-sm-4"><small class="text-muted d-block">Valid until</small><strong>{{ $currentMembership->end_date->format('M j, Y') }}</strong></div>
                @if($currentMembership->payment)
                    <div class="col-sm-4"><small class="text-muted d-block">Paid</small><strong>${{ number_format($currentMembership->payment->amount, 2) }}</strong><small class="d-block text-muted">{{ $currentMembership->payment->payment_method }} · {{ $currentMembership->payment->reference_number }}</small></div>
                @endif
            </div>
            <form action="{{ route('memberships.cancel',$currentMembership) }}" method="POST" class="mt-4">@csrf<button type="submit" class="btn btn-outline-danger" data-confirm="Cancel your active membership?">Cancel Membership</button></form></div></div></div></div></div>
    @else
        @if($currentMembership)
            <div class="alert alert-secondary d-flex align-items-center gap-2 mb-4" role="status"><i class="bi bi-info-circle"></i><span>Your {{ $currentMembership->membershipType->name }} membership was <strong>{{ $currentMembership->status === 'cancelled' ? 'cancelled' : 'not renewed' }}</strong>. You can join again below.</span></div>
        @endif
        <div class="text-center mb-5"><p class="section-kicker">Choose your level</p><h1 class="section-title">Membership made rewarding</h1><p class="section-copy mx-auto">Select the tier that matches how you stay and enjoy benefits throughout its membership period.</p></div>
        <div class="row g-4 justify-content-center">@forelse($membershipTypes as $type)<div class="col-md-6 col-lg-4"><article class="hotel-card p-4 p-lg-5 d-flex flex-column"><span class="metric-icon"><i class="bi bi-gem"></i></span><p class="section-kicker">{{ $type->duration_months }} months</p><h2 class="h3">{{ $type->name }}</h2><p class="text-muted flex-grow-1">{{ $type->description ?: 'Extra value for returning hotel guests.' }}</p><p class="display-6 font-display mb-1">{{ number_format($type->discount_percentage, 0) }}%</p><p class="text-muted mb-2">off eligible bookings</p><p class="fw-semibold mb-0">{{ $type->price > 0 ? '$'.number_format($type->price, 2) : 'Free' }}</p><a href="{{ route('memberships.checkout', $type) }}" class="btn btn-hotel w-100 mt-3">Join {{ $type->name }}</a></article></div>@empty<div class="col-12"><x-empty-state icon="bi-award" title="Memberships coming soon" message="No active membership tiers are currently available." /></div>@endforelse</div>
    @endif
</div></section>
@endsection
