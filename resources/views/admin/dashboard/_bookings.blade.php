<section class="dashboard-panel mb-3">
<header class="dashboard-panel-header"><div><h2>{{ $heading }}</h2><span>Showing up to five stays</span></div><a href="{{ route('bookings.index') }}" class="dashboard-panel-link">All bookings</a></header>
<div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th>Booking / guest</th><th>Room</th><th>{{ $dateLabel ?? 'Stay' }}</th><th>Booking</th><th>Payment</th><th>Total</th><th></th></tr></thead><tbody>
@forelse($records as $booking)
<tr><td><strong>#{{ $booking->id }}</strong><small>{{ $booking->user?->name ?? 'Customer unavailable' }}</small></td><td>{{ $booking->room?->room_number ?? '—' }}</td><td>{{ $dateField ? $booking->{$dateField} : $booking->check_in_date.' → '.$booking->check_out_date }}</td><td><x-status-badge :status="$booking->status" /></td><td>@if($payment = $booking->payments->sortBy('id')->first())<x-status-badge :status="$payment->status" />@else<span class="small text-muted">Not recorded</span>@endif</td><td>${{ number_format($booking->total_amount, 2) }}</td><td><a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">View Booking</a></td></tr>
@empty<tr><td colspan="7" class="text-muted p-3">No stays to show.</td></tr>@endforelse
</tbody></table></div></section>
