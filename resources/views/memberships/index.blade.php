@extends('layouts.app')
@section('title', 'Membership')
@section('content')
<section class="page-hero membership-hero text-center"><div class="container">
    <p class="section-kicker">Hotel membership</p>
    <h1>Choose Your Membership</h1>
    <p class="mx-auto">Enjoy exclusive booking discounts and discover future loyalty rewards with every stay.</p>
</div></section>
<section class="content-section compact membership-plans"><div class="container">
    @if($currentMembership)
        <section class="hotel-card membership-current p-3 p-md-4 mb-4" aria-labelledby="your-membership-heading">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="metric-icon mb-0" aria-hidden="true"><i class="bi bi-award"></i></span>
                    <div><p class="section-kicker mb-1">Active membership</p><h2 id="your-membership-heading" class="h5 mb-1">Your Membership · {{ $currentMembership->membershipType->name }}</h2>
                        @if($currentMembership->discountPercentage() > 0)
                            <p class="mb-1">{{ $currentMembership->discountPercentage() }}% booking discount</p>
                        @else
                            <p class="mb-1 text-muted">A booking discount is not currently available for this plan.</p>
                        @endif
                        <p class="small text-muted mb-0">Valid until {{ $currentMembership->end_date->format('F j, Y') }}</p>
                    </div>
                </div>
                <form action="{{ route('memberships.cancel', $currentMembership) }}" method="POST">@csrf<button type="submit" class="btn btn-outline-danger btn-sm" data-confirm="Cancel your active membership?">Cancel Membership</button></form>
            </div>
        </section>
    @endif
    <div class="row g-4 justify-content-center">
        @forelse($membershipTypes as $type)
            @php
                $fallbackDescriptions = ['silver' => 'Essential savings for occasional stays.', 'gold' => 'More value for returning guests.', 'platinum' => 'Maximum savings for frequent stays.'];
                $tierStyle = in_array(strtolower($type->name), ['silver', 'gold', 'platinum'], true) ? strtolower($type->name) : 'standard';
            @endphp
            <div class="col-md-6 col-lg-4">
                <article class="hotel-card membership-plan membership-plan-{{ $tierStyle }} p-4 d-flex flex-column h-100" aria-labelledby="membership-plan-{{ $type->id }}">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <span class="membership-plan-icon" aria-hidden="true"><i class="bi {{ $tierStyle === 'platinum' ? 'bi-gem' : 'bi-award' }}"></i></span>
                        @if($tierStyle === 'gold')<span class="membership-premium-label membership-popular-label">Most Popular</span>@endif
                        @if($tierStyle === 'platinum')<span class="membership-premium-label">Highest tier</span>@endif
                    </div>
                    <h2 id="membership-plan-{{ $type->id }}" class="h3 mb-1">{{ $type->name }}</h2>
                    <p class="membership-plan-description small text-muted mb-3" title="{{ $type->description ?: ($fallbackDescriptions[$tierStyle] ?? 'Extra value for your next stay.') }}">{{ $type->description ?: ($fallbackDescriptions[$tierStyle] ?? 'Extra value for your next stay.') }}</p>
                    @if($type->price !== null)
                        <p class="membership-plan-price mb-0">${{ number_format($type->price, 2) }}</p>
                        <p class="small text-muted mb-3">{{ $type->duration_months == 12 ? 'per year' : 'per '.$type->duration_months.' months' }}</p>
                    @else
                        <p class="membership-plan-price membership-price-pending mb-0">Price coming soon</p><p class="small text-muted mb-3">{{ $type->duration_months }}-month membership</p>
                    @endif
                    <div class="membership-plan-discount mb-3">
                        <strong>{{ rtrim(rtrim(number_format($type->discount_percentage, 2), '0'), '.') }}% OFF</strong>
                        <span>room and package bookings</span>
                    </div>
                    <ul class="membership-plan-benefits list-unstyled flex-grow-1 mb-4">
                        <li><i class="bi bi-check2" aria-hidden="true"></i><span>1 point per $1 actually paid on eligible stays</span></li>
                        @if($type->loyalty_upgrade_points !== null)
                            <li><i class="bi bi-check2" aria-hidden="true"></i><span>{{ number_format($type->loyalty_upgrade_points) }} points toward your next tier</span></li>
                        @elseif($tierStyle === 'platinum')
                            <li><i class="bi bi-check2" aria-hidden="true"></i><span>Enjoy our highest membership tier</span></li>
                        @endif
                        <li><i class="bi bi-check2" aria-hidden="true"></i><span>Valid for {{ $type->duration_months }} months</span></li>
                    </ul>
                    <button type="button" class="btn btn-hotel membership-purchase w-100" disabled aria-describedby="membership-purchase-note">Purchase Membership</button>
                </article>
            </div>
        @empty
            <div class="col-12"><x-empty-state icon="bi-award" title="Memberships coming soon" message="No active membership tiers are currently available." /></div>
        @endforelse
    </div>
    @if($membershipTypes->isNotEmpty())<p id="membership-purchase-note" class="membership-purchase-note small mt-3 mb-0" role="note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Secure online membership purchase will be available once payment integration is enabled.</span></p>@endif
    <section class="membership-loyalty mt-4 p-3 p-md-4" aria-labelledby="loyalty-heading">
        <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-3"><h2 id="loyalty-heading" class="h5 mb-0">How loyalty works</h2><p class="small text-muted mb-0">Coming with payment and loyalty integration.</p></div>
        <ol class="row g-3 list-unstyled mb-0">
            <li class="col-md-4 d-flex align-items-start gap-2"><span class="membership-loyalty-step">1</span><div><h3 class="h6 mb-1">Join</h3><p class="small text-muted mb-0">Choose your membership when purchases open.</p></div></li>
            <li class="col-md-4 d-flex align-items-start gap-2"><span class="membership-loyalty-step">2</span><div><h3 class="h6 mb-1">Stay &amp; Earn</h3><p class="small text-muted mb-0">Active members earn 1 point per $1 actually paid on eligible stays.</p></div></li>
            <li class="col-md-4 d-flex align-items-start gap-2"><span class="membership-loyalty-step">3</span><div><h3 class="h6 mb-1">Upgrade</h3><p class="small text-muted mb-0">Reach the required points to unlock your next tier.</p></div></li>
        </ol>
    </section>
</div></section>
@endsection
