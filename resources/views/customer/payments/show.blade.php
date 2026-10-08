@extends('layouts.app')
@section('title', 'Payment '.$payment->reference_number)
@section('content')
<section class="content-section compact"><div class="container" style="max-width:800px">
    <article class="checkout-summary p-4 p-md-5">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><p class="section-kicker">{{ $payment->purposeLabel() }}</p><h1 class="h3">{{ $payment->status === 'Paid' ? 'Payment recorded' : ($payment->status === 'Refunded' ? 'Payment refunded' : 'Awaiting payment verification') }}</h1></div><x-status-badge :status="$payment->status" /></div>
        <p class="text-muted">@if($payment->payment_method === 'Card') Demo Card Payment — simulated for this project. No bank transaction occurred. @elseif($payment->status === 'Pending' && $payment->payment_method === 'Cash at Hotel') Payment pending — pay at hotel. @elseif($payment->status === 'Pending') Your reference has been recorded. Hotel staff will verify your transfer manually. @endif</p>
        @include('payments._purpose', ['customerContext' => true])
        <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 border-top border-bottom py-3 my-4"><span>Payment amount</span><strong class="payment-total">${{ number_format($payment->amount, 2) }}</strong></div>
        <dl class="row small mb-4"><dt class="col-sm-4">Payment reference</dt><dd class="col-sm-8 text-break">{{ $payment->reference_number }}</dd><dt class="col-sm-4">Method</dt><dd class="col-sm-8">{{ $payment->payment_method }}</dd><dt class="col-sm-4">Date</dt><dd class="col-sm-8">{{ $payment->payment_date->format('M j, Y') }}</dd>@if($payment->transaction_reference)<dt class="col-sm-4">Transaction reference</dt><dd class="col-sm-8 text-break">{{ $payment->transaction_reference }}</dd>@endif</dl>
        @if($payment->membership_purchase_id && $payment->status === 'Pending')<p class="small text-muted">Your membership will activate only after the hotel records this payment as Paid.</p>@endif
        <div class="d-flex flex-wrap gap-2"><a class="btn btn-hotel" href="{{ route('customer.payments.receipt', $payment) }}">View Receipt</a><a class="btn btn-outline-secondary" href="{{ $payment->booking_id ? route('customer.bookings.show', $payment->booking_id) : route('memberships.index') }}">{{ $payment->booking_id ? 'Back to booking' : 'Memberships' }}</a></div>
    </article>
</div></section>
@endsection
