@php($packageTotal = $booking->packageTotalCents() / 100)
<div class="payment-breakdown border-top pt-3 mt-3">
    <div class="d-flex justify-content-between gap-3 mb-2"><span>Room stay</span><strong>${{ number_format($booking->subtotalCents() / 100 - $packageTotal, 2) }}</strong></div>
    @foreach($booking->bookingPackages as $line)
        <div class="d-flex justify-content-between gap-3 mb-2 small"><span>{{ $line->package->name }} × {{ $line->quantity }}</span><span>${{ number_format((int) round((float) $line->price * 100) * $line->quantity / 100, 2) }}</span></div>
    @endforeach
    @if($booking->bookingPackages->isNotEmpty())<div class="d-flex justify-content-between gap-3 pt-2 mb-2"><span>Package amount</span><strong>${{ number_format($packageTotal, 2) }}</strong></div>@endif
    @if((float) $booking->membership_discount_amount > 0)
        <div class="d-flex justify-content-between gap-3 pt-2 border-top"><span>Membership discount<small class="d-block text-muted">{{ $booking->membership_name }} · {{ number_format($booking->membership_discount_percentage, 2) }}%</small></span><span class="text-success">−${{ number_format($booking->membership_discount_amount, 2) }}</span></div>
    @endif
    <div class="d-flex justify-content-between gap-3 mt-3 pt-3 border-top"><strong>Final total</strong><strong>${{ number_format($booking->total_amount, 2) }}</strong></div>
</div>
