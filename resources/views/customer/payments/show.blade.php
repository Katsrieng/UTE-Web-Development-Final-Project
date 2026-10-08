@extends('layouts.app')
@section('title', 'Payment '.$payment->reference_number)
@section('content')
<section class="content-section compact"><div class="container" style="max-width:800px">
    <article class="checkout-summary p-4 p-md-5">
        @php
            $paid = $payment->status === 'Paid';
            $refunded = $payment->status === 'Refunded';
            $cash = in_array($payment->payment_method, ['Cash', 'Cash at Hotel'], true);
            $heading = $paid ? 'Payment Successful' : ($refunded ? 'Payment Refunded' : ($cash ? 'Payment Pending' : 'Payment Submitted'));
        @endphp
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div class="d-flex align-items-center gap-3">
                <span class="payment-result-icon {{ $paid ? 'is-paid' : '' }}"><i class="bi {{ $paid ? 'bi-check2-circle' : ($refunded ? 'bi-arrow-counterclockwise' : 'bi-clock') }}" aria-hidden="true"></i></span>
                <div><p class="section-kicker mb-1">{{ $payment->purposeLabel() }}</p><h1 class="h3 mb-0">{{ $heading }}</h1></div>
            </div>
            <x-status-badge :status="$payment->status" />
        </div>
        <div class="text-muted mb-4">
            @if($paid)
                @if($payment->booking?->status === 'Confirmed')<p class="mb-1">Your booking is now confirmed.</p>@endif
                @if($payment->membershipPurchase?->membership?->status === 'active')<p class="mb-1">Your membership is now active.</p>@endif
                @if($payment->payment_method === 'Card')<p class="small mb-0">Demo card payment — simulated for this project. No real card was charged.</p>@endif
            @elseif($refunded)
                <p class="mb-0">This payment has been recorded as refunded.</p>
            @elseif($cash)
                <p class="fw-semibold mb-1">Pay at the hotel</p><p class="small mb-0">No online payment is required. Hotel staff will collect payment and mark it Paid. {{ $payment->booking_id ? 'Your booking will then be confirmed.' : 'Your membership will then activate.' }}</p>
            @else
                <p class="fw-semibold mb-1">Awaiting hotel verification</p><p class="small mb-0">Your payment remains Pending until hotel staff verifies the transaction.</p>
            @endif
        </div>
        @include('payments._purpose', ['customerContext' => true])
        <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 border-top border-bottom py-3 my-4"><span>Payment amount</span><strong class="payment-total">${{ number_format($payment->amount, 2) }}</strong></div>
        <dl class="row small mb-4"><dt class="col-sm-4">Payment reference</dt><dd class="col-sm-8 text-break">{{ $payment->reference_number }}</dd><dt class="col-sm-4">Method</dt><dd class="col-sm-8">{{ $payment->payment_method }}</dd><dt class="col-sm-4">Date</dt><dd class="col-sm-8">{{ $payment->payment_date->format('M j, Y') }}</dd>@if($payment->transaction_reference)<dt class="col-sm-4">Transaction reference</dt><dd class="col-sm-8 text-break">{{ $payment->transaction_reference }}</dd>@endif</dl>
        @if($payment->membership_purchase_id && $payment->status === 'Pending' && ! $cash)<p class="small text-muted">Your membership activates after staff records payment as Paid.</p>@endif
        @if($payment->booking?->paymentSlip && $payment->payment_method === 'ABA / KHQR')<div class="payment-instructions mb-3"><i class="bi bi-receipt" aria-hidden="true"></i><div><span class="small text-muted d-block">Submitted slip</span><a class="small text-break" href="{{ $payment->booking->paymentSlip->url() }}" target="_blank" rel="noopener">{{ $payment->booking->paymentSlip->original_filename }}</a></div></div>@endif
        @if($payment->booking_id && $payment->status === 'Pending' && $payment->payment_method === 'ABA / KHQR')<a class="btn btn-outline-secondary mb-3" href="{{ route('customer.payments.booking', $payment->booking_id) }}">View QR / Manage payment slip</a>@endif
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-hotel" href="{{ route('customer.payments.receipt', $payment) }}">View Receipt</a><a class="btn btn-outline-secondary" href="{{ $payment->booking_id ? route('customer.bookings.show', $payment->booking_id) : route('memberships.index') }}">{{ $payment->booking_id ? 'Back to booking' : 'Memberships' }}</a></div>
    </article>
</div></section>
@endsection
