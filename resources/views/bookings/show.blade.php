@extends('layouts.management')

@section('title', 'Booking #' . $booking->id)
@section('page-label', 'Bookings')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="{{ route('bookings.index') }}" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Back to bookings
    </a>
    <div class="d-flex gap-2">
        <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i> Edit Booking
        </a>
    </div>
</div>

@if($errors->any())
    <div class="validation-summary mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if($booking->paymentSlip)
    <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4" role="alert">
        <div>
            <i class="bi bi-receipt-cutoff me-1"></i>
            <strong>Payment Slip Uploaded:</strong>
            <span>{{ $booking->paymentSlip->original_filename }} ({{ $booking->paymentSlip->created_at->diffForHumans() }})</span>
            @if($booking->paymentSlip->reviewed)
                <span class="badge bg-success ms-2">Reviewed</span>
            @else
                <span class="badge bg-warning text-dark ms-2">Needs Review</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="#payment-slip-section" class="btn btn-sm btn-primary">
                <i class="bi bi-eye me-1"></i> Review Slip
            </a>
            <a href="{{ route('payments.create', ['user_id' => $booking->user_id, 'booking_id' => $booking->id, 'amount' => $booking->total_amount, 'payment_method' => 'Bank Transfer']) }}" class="btn btn-sm btn-success">
                <i class="bi bi-cash-coin me-1"></i> Record Payment
            </a>
        </div>
    </div>
@endif

<div class="row g-4">
    {{-- Left Column: Booking Details & History --}}
    <div class="col-lg-8">
        {{-- Booking Summary Card --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white p-4 d-flex justify-content-between gap-3 align-items-center border-bottom">
                <div>
                    <p class="section-kicker mb-1">Reservation #{{ $booking->id }}</p>
                    <h1 class="h3 mb-0">Room {{ $booking->room->room_number }}</h1>
                    <small class="text-muted">{{ $booking->room->roomType?->name ?? 'Standard Room' }}</small>
                </div>
                <x-status-badge :status="$booking->status" class="fs-6 px-3 py-2" />
            </div>

            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Customer</small>
                        <strong class="fs-6">{{ $booking->user->name }}</strong>
                        <small class="d-block text-muted">{{ $booking->user->email }}</small>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted d-block">Total Amount</small>
                        <strong class="fs-4 text-success">${{ number_format($booking->total_amount, 2) }}</strong>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted d-block">Check-in Date</small>
                        <strong>{{ \Carbon\Carbon::parse($booking->check_in_date)->format('M j, Y') }}</strong>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted d-block">Check-out Date</small>
                        <strong>{{ \Carbon\Carbon::parse($booking->check_out_date)->format('M j, Y') }}</strong>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted d-block">Number of Guests</small>
                        <strong>{{ $booking->number_of_guests }} {{ Str::plural('Guest', $booking->number_of_guests) }}</strong>
                    </div>

                    <div class="col-md-6">
                        <small class="text-muted d-block">Created On</small>
                        <span>{{ $booking->created_at?->format('M j, Y g:i A') }}</span>
                    </div>

                    @if($booking->special_request)
                        <div class="col-12">
                            <hr class="my-2">
                            <small class="text-muted d-block mb-1">Special Request</small>
                            <p class="mb-0 bg-light p-3 rounded text-secondary">{{ $booking->special_request }}</p>
                        </div>
                    @endif
                </div>

                @include('bookings._package-summary')
            </div>
        </div>

        {{-- Status Transition History Card --}}
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white p-4 border-bottom">
                <h2 class="h5 mb-0"><i class="bi bi-clock-history me-2"></i>Status History</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">From</th>
                            <th>To</th>
                            <th>Changed By</th>
                            <th>Note</th>
                            <th class="text-end pe-4">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($booking->statusLogs as $log)
                            <tr>
                                <td class="ps-4">
                                    @if($log->old_status)
                                        <x-status-badge :status="$log->old_status" />
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :status="$log->new_status" />
                                </td>
                                <td>
                                    <strong>{{ $log->changedBy?->name ?? 'System' }}</strong>
                                </td>
                                <td class="text-muted">
                                    {{ $log->note ?? '&mdash;' }}
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                    {{ $log->created_at?->format('M j, Y g:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No status changes recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right Column: Actions & Payment Slip Review --}}
    <div class="col-lg-4">
        {{-- Status Transitions Card --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 mb-3">Booking Actions</h2>
                <div class="d-grid gap-2">
                    @foreach([
                        'Confirmed'   => ['confirm', 'Confirm Booking', 'btn-success', 'bi-check2-circle'],
                        'Checked In'  => ['check-in', 'Check In', 'btn-primary', 'bi-door-open'],
                        'Checked Out' => ['check-out', 'Check Out', 'btn-outline-primary', 'bi-door-closed'],
                        'Cancelled'   => ['cancel', 'Cancel Booking', 'btn-outline-danger', 'bi-x-circle'],
                    ] as $status => [$action, $label, $buttonClass, $icon])
                        @if($booking->canTransitionTo($status))
                            <form action="{{ route('bookings.'.$action, $booking) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn {{ $buttonClass }} w-100">
                                    <i class="bi {{ $icon }} me-1"></i> {{ $label }}
                                </button>
                            </form>
                        @endif
                    @endforeach

                    <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i> Edit Reservation
                    </a>
                </div>
            </div>
        </div>

        {{-- Payment Slip Review Card --}}
        @if($booking->paymentSlip)
            <div class="card shadow-sm border-0 mb-4" id="payment-slip-section">
                <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <p class="section-kicker mb-1">Customer payment</p>
                        <h2 class="h5 mb-0">Payment Slip (KHQR / ABA)</h2>
                    </div>
                    @if($booking->paymentSlip->reviewed)
                        <span class="badge bg-success">Reviewed</span>
                    @else
                        <span class="badge bg-warning text-dark">Needs Review</span>
                    @endif
                </div>

                <div class="card-body p-4 text-center">
                    <a href="{{ $booking->paymentSlip->url() }}" target="_blank" title="Click to view full image">
                        <img src="{{ $booking->paymentSlip->url() }}" alt="Customer payment slip" class="img-fluid rounded border shadow-sm mb-3" style="max-height: 240px; object-fit: contain;">
                    </a>

                    <div class="text-start small text-muted mb-3">
                        <div><strong>File:</strong> {{ $booking->paymentSlip->original_filename }}</div>
                        <div><strong>Uploaded:</strong> {{ $booking->paymentSlip->created_at->format('M j, Y g:i A') }}</div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="{{ $booking->paymentSlip->url() }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrows-fullscreen me-1"></i> Open Full Size
                        </a>
                        <a href="{{ route('payments.create', ['user_id' => $booking->user_id, 'booking_id' => $booking->id, 'amount' => $booking->total_amount, 'payment_method' => 'Bank Transfer']) }}" class="btn btn-hotel">
                            <i class="bi bi-check-circle me-1"></i> Record Official Payment
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 mb-2">Record Payment</h2>
                    <p class="small text-muted mb-3">Record an official cash, card, or transfer payment for this booking.</p>
                    <a href="{{ route('payments.create', ['user_id' => $booking->user_id, 'booking_id' => $booking->id, 'amount' => $booking->total_amount, 'payment_method' => 'Cash']) }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-plus-lg me-1"></i> Add Payment Record
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
