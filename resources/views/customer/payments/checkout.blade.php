@extends('layouts.app')
@section('title', 'Complete Your Payment')
@section('content')
<section class="content-section compact"><div class="container payment-checkout">
    <div class="page-heading"><div><p class="section-kicker">Your hotel checkout</p><h1 class="h2">Complete Your Payment</h1><p>Review your {{ $booking ? 'stay' : 'membership' }} and choose how to pay.</p></div><a class="btn btn-outline-secondary btn-sm" href="{{ $booking ? route('customer.bookings.show', $booking) : route('memberships.index') }}">Back</a></div>
    @if($errors->any())<div class="validation-summary mb-4" role="alert"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><div><strong>Please review your payment</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <div class="row g-4">
        <div class="col-lg-5"><section class="checkout-inputs payment-stay p-4">
            <p class="section-kicker">{{ $booking ? 'Your stay' : 'Your membership' }}</p>
            @if($booking)
                <h2 class="h4">Room {{ $booking->room->room_number }} · {{ $booking->room->roomType->name }}</h2>
                <p class="text-muted">{{ Carbon\Carbon::parse($booking->check_in_date)->format('M j, Y') }} → {{ Carbon\Carbon::parse($booking->check_out_date)->format('M j, Y') }}</p>
                <p class="small text-muted">{{ Carbon\Carbon::parse($booking->check_in_date)->diffInDays(Carbon\Carbon::parse($booking->check_out_date)) }} nights · {{ $booking->number_of_guests }} guests</p>
                @include('bookings._package-summary')
            @else
                <h2 class="h3">{{ $plan->name }} Membership</h2><p class="text-muted">12 months of member benefits, starting once payment is recorded as Paid.</p>
                <ul class="list-unstyled payment-benefits"><li><i class="bi bi-check2" aria-hidden="true"></i> {{ number_format($plan->discount_percentage, 2) }}% off room and package bookings</li><li><i class="bi bi-check2" aria-hidden="true"></i> Annual plan · ${{ number_format($amount, 2) }}</li>@if($plan->loyalty_upgrade_points)<li><i class="bi bi-check2" aria-hidden="true"></i> {{ number_format($plan->loyalty_upgrade_points) }} points toward your next tier <span class="text-muted small">(future loyalty program)</span></li>@endif</ul>
                <p class="small text-muted mb-0">Loyalty earning and tier upgrades are not available yet.</p>
            @endif
        </section></div>
        <div class="col-lg-7"><section class="checkout-summary p-4">
            <div class="d-flex align-items-center justify-content-between gap-3 pb-3 mb-3 border-bottom"><div><h2 class="h5 mb-1">Payment summary</h2><span class="small text-muted">{{ $booking ? 'Hotel Booking #'.$booking->id : $plan->name.' annual membership' }}</span></div><strong class="payment-total">${{ number_format($amount, 2) }}</strong></div>
            <form action="{{ $action }}" method="POST" data-payment-checkout>@csrf
                <fieldset class="mb-3"><legend class="fs-6 fw-semibold mb-2">Payment method</legend><div class="payment-method-grid">
                    @foreach(['Card' => 'bi-credit-card', 'ABA / KHQR' => 'bi-qr-code', 'Cash at Hotel' => 'bi-cash-stack'] as $method => $icon)
                        <label class="payment-method-tile"><input type="radio" name="payment_method" value="{{ $method }}" @checked(old('payment_method', 'Cash at Hotel') === $method) required><i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $method }}@if($method === 'Card')<small class="d-block text-muted">Demo simulation</small>@endif</span></label>
                    @endforeach
                </div></fieldset>
                <fieldset id="demoCardFields" class="payment-card-fields" disabled hidden>
                    <legend class="h6 mb-1"><i class="bi bi-credit-card me-1" aria-hidden="true"></i> Demo Card Payment</legend>
                    <p class="small text-muted mb-3" id="demo-card-notice">Use demo details only. Card payment is simulated for this project; no money is transferred. Card details stay in your browser.</p>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label" for="demoCardholder">Cardholder Name</label><input id="demoCardholder" type="text" class="form-control" placeholder="Alex Morgan" maxlength="80" autocomplete="off" required aria-describedby="demo-card-notice"></div>
                        <div class="col-12"><label class="form-label" for="demoCardNumber">Card Number</label><input id="demoCardNumber" type="text" inputmode="numeric" class="form-control" placeholder="4242 4242 4242 4242" maxlength="23" autocomplete="off" required pattern="[0-9 ]{13,23}"></div>
                        <div class="col-sm-6"><label class="form-label" for="demoCardExpiry">Expiry Date</label><input id="demoCardExpiry" type="text" inputmode="numeric" class="form-control" placeholder="MM / YY" maxlength="7" autocomplete="off" required pattern="(0[1-9]|1[0-2]) / [0-9]{2}"></div>
                        <div class="col-sm-6"><label class="form-label" for="demoCardCvv">CVV</label><input id="demoCardCvv" type="password" inputmode="numeric" class="form-control" placeholder="123" maxlength="4" autocomplete="off" required pattern="[0-9]{3,4}"></div>
                    </div>
                </fieldset>
                <div id="manualPaymentFields" hidden>
                    <div class="payment-instructions mb-3"><i class="bi bi-info-circle" aria-hidden="true"></i><div><strong>Manual hotel verification</strong><p class="small mb-0">Contact the hotel for its verified bank details or ABA/KHQR code before sending funds. No live QR or bank API is connected. Enter your transaction reference below; staff will verify payment.</p></div></div>
                    <label class="form-label" for="transaction_reference">Transaction/reference number</label><input id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference') }}" type="text" maxlength="191" class="form-control" disabled>
                </div>
                <p id="paymentNextStep" class="small text-muted mt-3 mb-3" role="status">Payment pending — pay at hotel. Staff will record collection.</p>
                <button type="submit" class="btn btn-hotel w-100" id="paymentSubmit" data-amount="${{ number_format($amount, 2) }}">Choose Pay at Hotel</button>
                <p class="small text-muted text-center mt-2 mb-0"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Your amount comes from the hotel record. Card details are never submitted or saved.</p>
            </form>
        </section></div>
    </div>
</div></section>
@endsection
@push('scripts')<script src="{{ asset('js/payment-checkout.js') }}" defer></script>@endpush
