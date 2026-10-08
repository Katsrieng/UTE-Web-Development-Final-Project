@extends('layouts.app')
@section('title', 'My Bookings')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">Your stays</p><h1>My Bookings</h1><p>View your room reservations and their current status.</p></div></section>
<section class="content-section compact"><div class="container">
    <div class="page-heading"><div><h2>Your reservations</h2></div><a href="{{ route('rooms.index') }}" class="btn btn-hotel">Browse Rooms</a></div>
    <x-list-toolbar :action="route('customer.bookings.index')" placeholder="Booking ID or room" :fields="['status'=>['label'=>'Booking status', 'all'=>'All bookings', 'options'=>array_combine(array_keys(App\Models\Booking::STATUS_TRANSITIONS), array_keys(App\Models\Booking::STATUS_TRANSITIONS))], 'payment_status'=>['label'=>'Payment status', 'all'=>'All payments', 'options'=>['Pending'=>'Pending','Paid'=>'Paid','Refunded'=>'Refunded']]]" />
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th class="ps-4">Booking</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Total</th><th>Booking / Payment</th><th class="pe-4">Details</th></tr></thead>
                <tbody>
                    @forelse($bookings as $booking)
                        @php($state = App\View\BookingStatusPresenter::forBooking($booking))
                        <tr>
                            <td class="ps-4">#{{ $booking->id }}</td>
                            <td><strong>Room {{ $booking->room->room_number }}</strong><small class="d-block text-muted">{{ $booking->room->roomType->name }}</small></td>
                            <td>{{ $booking->check_in_date }}</td><td>{{ $booking->check_out_date }}</td>
                            <td>${{ number_format($booking->total_amount, 2) }}</td>
                            <td><x-status-badge :status="$booking->status" /><small class="d-block mt-1">{{ $state['message'] }}</small><div class="mt-2">@include('customer.bookings._payment-status')</div></td>
                            <td class="pe-4"><a href="{{ route('customer.bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary">View details</a><div class="d-flex flex-wrap gap-2 mt-2">@include('customer.bookings._payment-actions')</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-list-no-results module="bookings" :clear="route('customer.bookings.index')" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $bookings->links() }}</div>
</div></section>
@endsection
