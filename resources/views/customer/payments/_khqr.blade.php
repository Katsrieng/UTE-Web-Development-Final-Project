<div class="payment-khqr mb-3 border-top pt-3">
    <h3 class="h6 mb-1"><i class="bi bi-qr-code me-1" aria-hidden="true"></i> Pay with ABA / KHQR</h3>
    <p class="small text-muted mb-3">Scan with ABA Mobile, Bakong, or any KHQR-supported banking app.</p>
    @if($paymentSettings->khqrAvailable())
        <div class="payment-qr-card text-center mb-3"><img src="{{ $paymentSettings->qrUrl() }}" alt="Hotel ABA / KHQR payment code" class="img-fluid"></div>
    @endif
    <div class="payment-qr-details mb-3">
        <div><span class="payment-detail-label">Payment account</span><strong class="d-block">{{ $paymentSettings->account_name }}</strong>@if($paymentSettings->account_label)<span class="d-block small text-muted">{{ $paymentSettings->account_label }}</span>@endif</div>
        <div><span class="payment-detail-label">{{ $booking ? 'Booking reference' : 'Membership' }}</span><strong class="d-block">{{ $booking ? '#'.$booking->id : $plan->name }}</strong></div>
        <div><span class="payment-detail-label">Amount due</span><strong class="d-block payment-qr-amount">${{ number_format($amount, 2) }}</strong></div>
    </div>
    @if($booking)
        @if($booking->paymentSlip)<p class="small mb-2">Current slip: <a href="{{ $booking->paymentSlip->url() }}" target="_blank" rel="noopener">{{ $booking->paymentSlip->original_filename }}</a></p>@endif
        <label for="payment_slip" class="form-label">{{ $booking->paymentSlip ? 'Replace payment slip / screenshot' : 'Payment slip / screenshot' }}</label>
        <input type="file" id="payment_slip" name="payment_slip" class="form-control mb-1" accept=".jpg,.jpeg,.png" disabled>
        @error('payment_slip')<p class="small text-danger mt-1 mb-1">{{ $message }}</p>@enderror
        <p class="form-text mb-3">JPG or PNG · Max 5 MB. Make the amount and reference readable.</p>
    @endif
    <label class="form-label" for="transaction_reference">Transaction/reference number{{ $booking ? ' (optional)' : '' }}</label>
    <input id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference', $existingPayment->transaction_reference ?? '') }}" type="text" maxlength="191" class="form-control" data-required="{{ $booking ? 'false' : 'true' }}" disabled>
    @error('transaction_reference')<p class="small text-danger mt-1 mb-0">{{ $message }}</p>@enderror
    <p class="small text-muted mt-2 mb-0">Your payment will remain Pending until hotel staff verifies the transaction.</p>
</div>
