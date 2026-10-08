<div class="small mb-2"><strong>{{ $payment->purposeLabel() }}</strong>
    @if($payment->booking_id)
        <a class="d-block" href="{{ !empty($customerContext) ? route('customer.bookings.show', $payment->booking_id) : route('bookings.show', $payment->booking_id) }}">Booking #{{ $payment->booking_id }} @if($payment->booking) · Room {{ $payment->booking->room?->room_number }}@endif</a>
    @elseif($payment->membership_purchase_id)
        <span class="d-block">{{ $payment->membershipPurchase?->membership_name }} Membership · Purchase #{{ $payment->membership_purchase_id }}</span>
    @elseif($payment->event_booking_id)
        <span class="d-block">Event booking #{{ $payment->event_booking_id }} · {{ $payment->eventBooking?->venue?->name ?? 'Venue unavailable' }}</span>
    @endif
</div>
