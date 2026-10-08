<section class="operations-kpis" aria-label="Daily hotel operations">
@foreach([['Today’s Arrivals', $operations['arrivals'], 'Confirmed arrivals today', route('bookings.index', ['status'=>'Confirmed', 'arrival_date'=>today()->toDateString()])], ['Today’s Departures', $operations['departures'], 'Checked-in departures today', route('bookings.index', ['status'=>'Checked In', 'departure_date'=>today()->toDateString()])], ['Checked-In Stays', $operations['checked_in'], 'Stays, not guest count', route('bookings.index', ['status'=>'Checked In'])], ['Available Rooms', $roomCounts['available'] ?? 0, 'Operational room status', route('management.rooms.index', ['status'=>'available'])]] as [$label, $count, $note, $url])
@php($metricPermission = $label === 'Available Rooms' ? 'view_rooms' : 'view_bookings')
@can($metricPermission)
<a class="operations-kpi" href="{{ $url }}"><span>{{ $label }}</span><strong>{{ $count }}</strong><small>{{ $note }}</small></a>
@else
<div class="operations-kpi"><span>{{ $label }}</span><strong>{{ $count }}</strong><small>{{ $note }}</small></div>
@endcan
@endforeach
</section>
<nav class="operations-attention" aria-label="Needs attention">

@staffroute('bookings.index')
<a href="{{ route('bookings.index', ['status'=>'Pending']) }}"><strong>{{ $operations['pending'] }}</strong> Pending Bookings</a>
@endstaffroute


@staffroute('payments.index')
<a href="{{ route('payments.index', ['status'=>'Pending']) }}"><strong>{{ $pendingPayments }}</strong> Pending Payments</a>
@endstaffroute


@staffroute('management.rooms.index')
<a href="{{ route('management.rooms.index', ['status'=>'cleaning']) }}"><strong>{{ $roomCounts['cleaning'] ?? 0 }}</strong> Cleaning Rooms</a>
@endstaffroute


@staffroute('management.event-reservations.index')
<a href="{{ route('management.event-reservations.index', ['status'=>'pending']) }}"><strong>{{ $pendingEvents }}</strong> Pending Event Reservations</a>
@endstaffroute

</nav>
<div class="row g-3"><div class="col-xxl-6">@include('admin.dashboard._bookings', ['heading'=>"Today's Arrivals", 'records'=>$arrivals, 'dateLabel'=>'Arrival', 'dateField'=>'check_in_date'])</div><div class="col-xxl-6">@include('admin.dashboard._bookings', ['heading'=>"Today's Departures", 'records'=>$departures, 'dateLabel'=>'Departure', 'dateField'=>'check_out_date'])</div></div>
<div class="row g-3 mb-4"><div class="col-lg-4"><section class="dashboard-panel h-100"><header class="dashboard-panel-header"><h2>Room operations</h2></header><div class="dashboard-panel-body room-operations">
@foreach(['available','occupied','cleaning','booked'] as $status)
@staffroute('management.rooms.index')
<a href="{{ route('management.rooms.index', ['status'=>$status]) }}"><span>{{ $status === 'booked' ? 'Legacy booked' : ucfirst($status) }}</span><strong>{{ $roomCounts[$status] ?? 0 }}</strong></a>
@endstaffroute
@endforeach
<p class="small text-muted mb-0">{{ $operations['confirmed'] }} Confirmed Bookings · Room counts reflect operational status.</p>
</div></section></div><div class="col-lg-8">
@can('view_payments')
<section class="dashboard-panel h-100"><header class="dashboard-panel-header"><h2>Payment attention</h2>
@staffroute('payments.index')
<a href="{{ route('payments.index', ['status'=>'Pending']) }}" class="dashboard-panel-link">All pending</a>
@endstaffroute
</header><div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th>Customer</th><th>Method / next step</th><th>Amount</th><th></th></tr></thead><tbody>
@forelse($paymentAttention as $payment)<tr><td>{{ $payment->user?->name ?? 'Customer unavailable' }}</td><td>{{ $payment->payment_method }}<small>{{ match($payment->payment_method) { 'ABA / KHQR' => 'Awaiting verification', 'Cash', 'Cash at Hotel' => 'Awaiting collection', default => 'Awaiting processing' } }}</small></td><td>${{ number_format($payment->amount,2) }}</td><td>
@staffroute('payments.show')
<a class="btn btn-sm btn-outline-primary" href="{{ route('payments.show',$payment) }}">View Payment</a>
@endstaffroute
</td></tr>@empty<tr><td colspan="4" class="text-muted p-3">No pending payments.</td></tr>@endforelse
</tbody></table></div></section>
@endcan
</div></div>
@can('view_payments')
<div class="operations-finance-heading"><h2>Financial overview</h2><span>Customer Card checkout is simulated for this project.</span></div>
@endcan
