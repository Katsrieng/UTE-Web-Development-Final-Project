<section class="operations-kpis" aria-label="Daily hotel operations">
@foreach([['Today’s Arrivals', $operations['arrivals'], 'Confirmed arrivals today', route('bookings.index', ['status'=>'Confirmed', 'arrival_date'=>today()->toDateString()])], ['Today’s Departures', $operations['departures'], 'Checked-in departures today', route('bookings.index', ['status'=>'Checked In', 'departure_date'=>today()->toDateString()])], ['Checked-In Stays', $operations['checked_in'], 'Stays, not guest count', route('bookings.index', ['status'=>'Checked In'])], ['Available Rooms', $roomCounts['available'] ?? 0, 'Operational room status', route('management.rooms.index', ['status'=>'available'])]] as [$label, $count, $note, $url])
<a class="operations-kpi" href="{{ $url }}"><span>{{ $label }}</span><strong>{{ $count }}</strong><small>{{ $note }}</small></a>
@endforeach
</section>
<nav class="operations-attention" aria-label="Needs attention">
<a href="{{ route('bookings.index', ['status'=>'Pending']) }}"><strong>{{ $operations['pending'] }}</strong> Pending Bookings</a>
<a href="{{ route('payments.index', ['status'=>'Pending']) }}"><strong>{{ $pendingPayments }}</strong> Pending Payments</a>
<a href="{{ route('management.rooms.index', ['status'=>'cleaning']) }}"><strong>{{ $roomCounts['cleaning'] ?? 0 }}</strong> Cleaning Rooms</a>
<a href="{{ route('management.event-reservations.index', ['status'=>'pending']) }}"><strong>{{ $pendingEvents }}</strong> Pending Event Reservations</a>
</nav>
<div class="row g-3"><div class="col-xxl-6">@include('admin.dashboard._bookings', ['heading'=>"Today's Arrivals", 'records'=>$arrivals, 'dateLabel'=>'Arrival', 'dateField'=>'check_in_date'])</div><div class="col-xxl-6">@include('admin.dashboard._bookings', ['heading'=>"Today's Departures", 'records'=>$departures, 'dateLabel'=>'Departure', 'dateField'=>'check_out_date'])</div></div>
<div class="row g-3 mb-4"><div class="col-lg-4"><section class="dashboard-panel h-100"><header class="dashboard-panel-header"><h2>Room operations</h2></header><div class="dashboard-panel-body room-operations">
@foreach(['available','occupied','cleaning','booked'] as $status)<a href="{{ route('management.rooms.index', ['status'=>$status]) }}"><span>{{ $status === 'booked' ? 'Legacy booked' : ucfirst($status) }}</span><strong>{{ $roomCounts[$status] ?? 0 }}</strong></a>@endforeach
<p class="small text-muted mb-0">{{ $operations['confirmed'] }} Confirmed Bookings · Room counts reflect operational status.</p>
</div></section></div><div class="col-lg-8"><section class="dashboard-panel h-100"><header class="dashboard-panel-header"><h2>Payment attention</h2><a href="{{ route('payments.index', ['status'=>'Pending']) }}" class="dashboard-panel-link">All pending</a></header><div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th>Customer</th><th>Method / next step</th><th>Amount</th><th></th></tr></thead><tbody>
@forelse($paymentAttention as $payment)<tr><td>{{ $payment->user?->name ?? 'Customer unavailable' }}</td><td>{{ $payment->payment_method }}<small>{{ match($payment->payment_method) { 'ABA / KHQR' => 'Awaiting verification', 'Cash', 'Cash at Hotel' => 'Awaiting collection', default => 'Awaiting processing' } }}</small></td><td>${{ number_format($payment->amount,2) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('payments.show',$payment) }}">View Payment</a></td></tr>@empty<tr><td colspan="4" class="text-muted p-3">No pending payments.</td></tr>@endforelse
</tbody></table></div></section></div></div>
<div class="operations-finance-heading"><h2>Financial overview</h2><span>Customer Card checkout is simulated for this project.</span></div>
