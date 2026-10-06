@if($booking->bookingPackages->isNotEmpty())
    @php($packageTotal = $booking->packageTotalCents() / 100)
    <section class="mt-4 pt-3 border-top" aria-label="Booking price breakdown">
        <h2 class="h5">Stay and add-ons</h2>
        <div class="d-flex justify-content-between gap-3 mb-3"><span>Room stay</span><strong>${{ number_format((float) $booking->total_amount - $packageTotal, 2) }}</strong></div>
        @foreach($booking->bookingPackages as $line)
            <div class="d-flex justify-content-between gap-3 mb-2"><div>{{ $line->package->name }}<small class="d-block text-muted">${{ number_format($line->price, 2) }} × {{ $line->quantity }}</small></div><span>${{ number_format((int) round((float) $line->price * 100) * $line->quantity / 100, 2) }}</span></div>
        @endforeach
        <div class="d-flex justify-content-between gap-3 mt-3"><span>Package total</span><strong>${{ number_format($packageTotal, 2) }}</strong></div>
        <div class="d-flex justify-content-between gap-3 mt-2 pt-2 border-top"><strong>Booking total</strong><strong>${{ number_format($booking->total_amount, 2) }}</strong></div>
    </section>
@endif
