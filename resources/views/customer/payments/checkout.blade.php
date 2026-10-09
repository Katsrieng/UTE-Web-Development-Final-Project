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
                <p class="small text-muted mb-2">{{ Carbon\Carbon::parse($booking->check_in_date)->format('M j, Y') }} → {{ Carbon\Carbon::parse($booking->check_out_date)->format('M j, Y') }}</p>
                <p class="small text-muted mb-0">{{ Carbon\Carbon::parse($booking->check_in_date)->diffInDays(Carbon\Carbon::parse($booking->check_out_date)) }} nights · {{ $booking->number_of_guests }} guests</p>
                @include('customer.payments._stay-summary')
            @else
                <h2 class="h3">{{ $plan->name }} Membership</h2><p class="text-muted">12 months of member benefits, starting once payment is recorded as Paid.</p>
                <ul class="list-unstyled payment-benefits"><li><i class="bi bi-check2" aria-hidden="true"></i> {{ number_format($plan->discount_percentage, 2) }}% off room and package bookings</li><li><i class="bi bi-check2" aria-hidden="true"></i> Annual plan · ${{ number_format($amount, 2) }}</li>@if($plan->loyalty_upgrade_points)<li><i class="bi bi-check2" aria-hidden="true"></i> {{ number_format($plan->loyalty_upgrade_points) }} points toward your next tier</li>@endif</ul>
                <p class="small text-muted mb-0">Earn 1 point per $1 paid on eligible stays while your paid membership is active.</p>
            @endif
        </section></div>
        <div class="col-lg-7"><section class="checkout-summary p-4">
            <div class="d-flex align-items-center justify-content-between gap-3 pb-3 mb-3 border-bottom"><div><h2 class="h5 mb-1">Payment Summary</h2><span class="small text-muted">{{ $booking ? 'Hotel Booking #'.$booking->id : $plan->name.' annual membership' }}</span></div><div class="text-end"><span class="small text-muted d-block">Amount due</span><strong class="payment-total">${{ number_format($amount, 2) }}</strong></div></div>
            <form action="{{ $action }}" method="POST" enctype="multipart/form-data" data-payment-checkout>@csrf
                <fieldset class="mb-3"><legend class="fs-6 fw-semibold mb-2">Payment method</legend><div class="payment-method-grid">
                    @foreach(['Card' => 'bi-credit-card', 'ABA / KHQR' => 'bi-qr-code', 'Cash at Hotel' => 'bi-cash-stack'] as $method => $icon)
                        @if($method === 'ABA / KHQR' && ! $paymentSettings->aba_khqr_enabled) @continue @endif
                        <label class="payment-method-tile"><input type="radio" name="payment_method" value="{{ $method }}" @checked((old('payment_method') === 'ABA / KHQR' && ! $paymentSettings->khqrAvailable() ? 'Cash at Hotel' : old('payment_method', $existingPayment->payment_method ?? 'Cash at Hotel')) === $method) @disabled(($method === 'ABA / KHQR' && ! $paymentSettings->khqrAvailable()) || (isset($existingPayment) && $existingPayment && $existingPayment->payment_method !== $method)) required><i class="bi {{ $icon }}" aria-hidden="true"></i><span>{{ $method }}<small class="d-block text-muted">{{ match($method) { 'Card' => 'Demo · paid now', 'ABA / KHQR' => 'Scan · staff verifies', default => 'Pay on arrival' } }}</small></span></label>
                    @endforeach
                </div></fieldset>
                @error('payment_method')<p class="small text-danger mb-2">{{ $message }}</p>@enderror
                @if(! $paymentSettings->khqrAvailable())<p class="small text-muted mb-3" role="status">ABA/KHQR payment is temporarily unavailable. Please choose another payment method.</p>@endif
                <fieldset id="demoCardFields" class="payment-card-fields" disabled hidden>
                    <legend class="h6 mb-1"><i class="bi bi-credit-card me-1" aria-hidden="true"></i> Demo card payment</legend>
                    <p class="small text-muted mb-3" id="demo-card-notice">Simulated for this project. Use demo details; no card is charged or stored.</p>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label" for="demoCardholder">Cardholder Name</label><input id="demoCardholder" type="text" class="form-control" placeholder="Alex Morgan" maxlength="80" autocomplete="off" required aria-describedby="demo-card-notice"></div>
                        <div class="col-12"><label class="form-label" for="demoCardNumber">Card Number</label><input id="demoCardNumber" type="text" inputmode="numeric" class="form-control" placeholder="4242 4242 4242 4242" maxlength="23" autocomplete="off" required pattern="[0-9 ]{13,23}"></div>
                        <div class="col-6"><label class="form-label" for="demoCardExpiry">Expiry Date</label><input id="demoCardExpiry" type="text" inputmode="numeric" class="form-control" placeholder="MM / YY" maxlength="7" autocomplete="off" required pattern="(0[1-9]|1[0-2]) / [0-9]{2}"></div>
                        <div class="col-6"><label class="form-label" for="demoCardCvv">CVV</label><input id="demoCardCvv" type="password" inputmode="numeric" class="form-control" placeholder="123" maxlength="4" autocomplete="off" required pattern="[0-9]{3,4}"></div>
                    </div>
                </fieldset>
                <div id="manualPaymentFields" hidden>
                    @include('customer.payments._khqr')
                </div>
                <div id="cashPaymentFields" class="payment-instructions mt-3">
                    <i class="bi bi-cash-stack" aria-hidden="true"></i><div><h3 class="h6 mb-1">Pay at the hotel</h3><p class="small mb-1">No online payment is required now. Your payment stays Pending until hotel staff collects it.</p><p class="small text-muted mb-0">{{ $booking ? 'Your booking is confirmed once staff records payment as Paid.' : 'Your membership activates once staff records payment as Paid.' }}</p></div>
                </div>
                <p id="paymentNextStep" class="small text-muted mt-3 mb-3" role="status">Payment pending — pay at hotel. Staff will record collection.</p>
                <button type="submit" class="btn btn-hotel w-100" id="paymentSubmit" data-amount="${{ number_format($amount, 2) }}">Confirm Pay at Hotel</button>
                <p class="small text-muted text-center mt-2 mb-0"><i class="bi bi-shield-check me-1" aria-hidden="true"></i>Your amount comes from the hotel record. Card details are never submitted or saved.</p>
            </form>
            @if($booking?->paymentSlip)
                <form method="POST" action="{{ route('customer.bookings.payment-slip.destroy', $booking) }}" class="mt-3">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Remove this payment slip?">Remove current slip</button></form>
            @endif
        </section></div>
    </div>
</div></section>
@endsection
@push('scripts')<script src="{{ asset('js/payment-checkout.js') }}" defer></script>@endpush
