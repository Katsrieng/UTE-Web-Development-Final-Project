@extends('layouts.management')

@section('title', 'Bookings')
@section('page-label', 'Bookings')

@section('content')
<div class="page-heading">
    <div>
        <p class="section-kicker">Reservation management</p>
        <h1>Bookings</h1>
        <p>Review customer room reservations, stay dates, guest counts, and statuses.</p>
    </div>

@staffroute('bookings.create')
<a href="{{ route('bookings.create') }}" class="btn btn-hotel">
        <i class="bi bi-plus-lg me-1"></i> Add Booking
    </a>
@endstaffroute

</div>

<x-list-toolbar :action="route('bookings.index')" placeholder="Customer, email, booking ID or room" :fields="['status'=>['label'=>'Booking status', 'all'=>'All bookings', 'options'=>array_combine(array_keys(App\Models\Booking::STATUS_TRANSITIONS), array_keys(App\Models\Booking::STATUS_TRANSITIONS))], 'payment_status'=>['label'=>'Payment status', 'all'=>'All payments', 'options'=>['Pending'=>'Pending','Paid'=>'Paid','Refunded'=>'Refunded']], 'arrival_date'=>['label'=>'Arrival date', 'type'=>'date'], 'departure_date'=>['label'=>'Departure date', 'type'=>'date']]" />
<div class="table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Booking</th>
                    <th>Customer</th>
                    <th>Room</th>
                    <th>Dates of Stay</th>
                    <th>Guests</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    <tr>
                        <td class="ps-4">
                            <strong>#{{ $booking->id }}</strong>
                            <small class="d-block text-muted">{{ $booking->created_at?->format('M j, Y') }}</small>
                        </td>
                        <td>
                            @if($booking->user)
                                <strong>{{ $booking->user->name }}</strong>
                                <small class="d-block text-muted">{{ $booking->user->email }}</small>
                            @else
                                <span class="text-muted">User #{{ $booking->user_id }}</span>
                            @endif
                        </td>
                        <td>
                            @if($booking->room)
                                <strong>Room {{ $booking->room->room_number }}</strong>
                                <small class="d-block text-muted">{{ $booking->room->roomType?->name ?? 'Standard' }}</small>
                            @else
                                <span class="text-muted">Room #{{ $booking->room_id }}</span>
                            @endif
                        </td>
                        <td>
                            <span>{{ \Carbon\Carbon::parse($booking->check_in_date)->format('M j, Y') }}</span>
                            <span class="text-muted"> &rarr; </span>
                            <span>{{ \Carbon\Carbon::parse($booking->check_out_date)->format('M j, Y') }}</span>
                        </td>
                        <td>
                            <span><i class="bi bi-people me-1 text-muted"></i>{{ $booking->number_of_guests }}</span>
                        </td>
                        <td class="fw-semibold">
                            ${{ number_format($booking->total_amount, 2) }}
                        </td>
                        <td>
                            <x-status-badge :status="$booking->status" />
                            @if($booking->paymentSlip)
                                <small class="d-block text-info mt-1"><i class="bi bi-paperclip"></i> Slip attached</small>
                            @endif
                        </td>
                        <td class="text-end pe-4 text-nowrap">

@staffroute('bookings.show')
<a href="{{ route('bookings.show', $booking) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
@endstaffroute


@staffroute('bookings.edit')
<a href="{{ route('bookings.edit', $booking) }}" class="btn btn-sm btn-outline-secondary" title="Edit Booking">
                                <i class="bi bi-pencil"></i>
                            </a>
@endstaffroute

                            @if(($booking->payments_count ?? 0) > 0)
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Cannot delete: payment records attached">
                                    <i class="bi bi-lock"></i>
                                </button>
                            @else

@staffroute('bookings.destroy')
<form action="{{ route('bookings.destroy', $booking) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Booking" onclick="return confirm('Are you sure you want to delete Booking #{{ $booking->id }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
@endstaffroute

                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-list-no-results module="bookings" :clear="route('bookings.index')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($bookings->hasPages())
    <div class="mt-4">
        {{ $bookings->links('pagination::bootstrap-5') }}
    </div>
@endif
@endsection
