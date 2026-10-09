@extends('layouts.app')
@section('title', 'Booking #'.$booking->id)
@section('content')
@php($state = App\View\BookingStatusPresenter::forBooking($booking))
<div class="breadcrumb-bar"><div class="container"><a href="{{ route('customer.bookings.index') }}"><i class="bi bi-arrow-left me-1"></i> My Bookings</a></div></div>
<section class="content-section compact"><div class="container"><div class="row justify-content-center"><div class="col-xl-9">
    @if($errors->any())
        <div class="validation-summary mb-4" role="alert"><i class="bi bi-exclamation-circle-fill"></i><div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif
    <div class="card booking-confirmation shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-white p-4 p-lg-5 d-flex flex-column flex-sm-row justify-content-between gap-3 align-items-sm-center">
            <div><p class="section-kicker d-flex align-items-center gap-2"><img class="confirmation-brand-logo" src="{{ asset('images/brand/utopia_bay_logo_color.svg') }}" alt="" width="28" height="28"> Utopia Bay · Booking #{{ $booking->id }}</p><h1 class="h2 mb-0">Room {{ $booking->room->room_number }}</h1><p class="text-muted mb-0 mt-2">{{ $booking->room->roomType->name }}</p></div>
            <x-status-badge :status="$booking->status" class="fs-6 px-3 py-2" />
        </div>
        <div class="card-body p-4 p-lg-5">
            <p class="booking-state-message mb-4">{{ $state['message'] }}</p>
            @include('bookings._package-summary')
            <div class="row g-4">
                <div class="col-md-6"><small class="text-muted d-block">Check-in</small><strong>{{ $booking->check_in_date }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Check-out</small><strong>{{ $booking->check_out_date }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Length of stay</small><strong>{{ $numberOfNights }} nights</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Number of guests</small><strong>{{ $booking->number_of_guests }}</strong></div>
                <div class="col-md-6"><div class="booking-confirmation-total"><small class="d-block">Booking total</small><strong>${{ number_format($booking->total_amount, 2) }}</strong></div></div>
                @if($booking->special_request)<div class="col-12"><hr><small class="text-muted d-block mb-1">Special request</small><p class="mb-0">{{ $booking->special_request }}</p></div>@endif
            </div>
        </div>
        <div class="px-4 pb-4">
            @include('customer.bookings._payment-status')
            <div class="d-flex flex-wrap gap-2 mt-3">@include('customer.bookings._payment-actions')</div>
        </div>
        @if($booking->canTransitionTo('Cancelled'))
            <div class="card-footer bg-white p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                <p class="text-muted small mb-0">You can cancel a Pending or Confirmed reservation.</p>
                <form method="POST" action="{{ route('customer.bookings.cancel', $booking) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger" data-confirm="Cancel this booking?">Cancel Booking</button>
                </form>
            </div>
        @endif
    </div>

    <section class="booking-history mt-4" aria-label="Booking timeline"><h2 class="h5">Booking timeline</h2><ol><li><strong>Created</strong><time>{{ $booking->created_at->format('M j, Y · H:i') }}</time></li>
    @foreach($booking->statusLogs->sortBy([['created_at','asc'],['id','asc']]) as $log)
        <li><strong>{{ $log->new_status }}</strong><time>{{ $log->created_at->format('M j, Y · H:i') }}</time></li>
        @if($log->new_status === 'Cancelled') @break @endif
    @endforeach
    </ol></section>
</div></div></div></section>
@endsection
