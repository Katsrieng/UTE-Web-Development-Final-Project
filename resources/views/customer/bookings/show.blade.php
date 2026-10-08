@extends('layouts.app')
@section('title', 'Booking #'.$booking->id)
@section('content')
<div class="breadcrumb-bar"><div class="container"><a href="{{ route('customer.bookings.index') }}"><i class="bi bi-arrow-left me-1"></i> My Bookings</a></div></div>
<section class="content-section compact"><div class="container"><div class="row justify-content-center"><div class="col-xl-9">
    @if($errors->any())
        <div class="validation-summary mb-4" role="alert"><i class="bi bi-exclamation-circle-fill"></i><div><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-white p-4 p-lg-5 d-flex flex-column flex-sm-row justify-content-between gap-3 align-items-sm-center">
            <div><p class="section-kicker">Booking #{{ $booking->id }}</p><h1 class="h2 mb-0">Room {{ $booking->room->room_number }}</h1><p class="text-muted mb-0 mt-2">{{ $booking->room->roomType->name }}</p></div>
            <x-status-badge :status="$booking->status" class="fs-6 px-3 py-2" />
        </div>
        <div class="card-body p-4 p-lg-5">
            @if($booking->status === 'Pending')<p class="text-muted mb-4">Your request is awaiting hotel confirmation.</p>@endif
            @include('bookings._package-summary')
            <div class="row g-4">
                <div class="col-md-6"><small class="text-muted d-block">Check-in</small><strong>{{ $booking->check_in_date }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Check-out</small><strong>{{ $booking->check_out_date }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Length of stay</small><strong>{{ $numberOfNights }} nights</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Number of guests</small><strong>{{ $booking->number_of_guests }}</strong></div>
                <div class="col-md-6"><small class="text-muted d-block">Total amount</small><strong>${{ number_format($booking->total_amount, 2) }}</strong></div>
                @if($booking->special_request)<div class="col-12"><hr><small class="text-muted d-block mb-1">Special request</small><p class="mb-0">{{ $booking->special_request }}</p></div>@endif
            </div>
        </div>
        <div class="px-4 pb-4">
            @if($payment = $booking->payments->sortBy('id')->first())
                <a class="btn btn-hotel" href="{{ route('customer.payments.show', $payment) }}">Payment: {{ $payment->status }}</a>
            @elseif(in_array($booking->status, App\Models\Booking::ACTIVE_STATUSES, true))
                <a class="btn btn-hotel" href="{{ route('customer.payments.booking', $booking) }}">Pay Now</a>
            @endif
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

    @if(in_array($booking->status, ['Pending', 'Confirmed', 'Checked In']))
        <div class="card shadow-sm border-0 overflow-hidden mt-4">
            <div class="card-header bg-white p-4 d-flex flex-column flex-sm-row justify-content-between gap-3 align-items-sm-center border-bottom">
                <div>
                    <p class="section-kicker mb-1"><i class="bi bi-qr-code me-1"></i> Payment Method</p>
                    <h2 class="h4 mb-0">Pay by QR Code (Cambodia KHQR & ABA)</h2>
                    <p class="text-muted small mb-0 mt-1">Scan using Bakong, ABA Mobile, or any Cambodian banking app</p>
                </div>
                <div class="text-sm-end">
                    <span class="text-muted d-block small">Amount Due</span>
                    <strong class="fs-4 text-success">${{ number_format($booking->total_amount, 2) }}</strong>
                </div>
            </div>

            <div class="card-body p-4 p-lg-5">
                @if($booking->payments->where('status', 'Paid')->isNotEmpty())
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div>
                            <strong>Payment Received!</strong>
                            <span>Your payment of ${{ number_format($booking->payments->where('status', 'Paid')->sum('amount'), 2) }} has been officially confirmed by our staff.</span>
                        </div>
                    </div>
                @endif

                <div class="row g-4 justify-content-center mb-4">
                    {{-- KHQR Column --}}
                    <div class="col-md-6 col-lg-5">
                        <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column align-items-center text-center">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-danger px-2 py-1">KHQR</span>
                                <span class="fw-semibold">Bakong & All Banks</span>
                            </div>
                            <div class="bg-white p-2 border rounded shadow-sm my-auto" style="max-width: 240px;">
                                <img src="{{ asset('images/qr-khqr.png') }}" alt="KHQR Code" class="img-fluid rounded">
                            </div>
                            <div class="mt-3 text-muted small">
                                <div><strong>Resort:</strong> Beach Resort Management</div>
                                <div><strong>Ref:</strong> Booking #{{ $booking->id }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- ABA PAY Column --}}
                    <div class="col-md-6 col-lg-5">
                        <div class="p-3 border rounded-3 bg-light h-100 d-flex flex-column align-items-center text-center">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-primary px-2 py-1">ABA PAY</span>
                                <span class="fw-semibold">ABA Mobile</span>
                            </div>
                            <div class="bg-white p-2 border rounded shadow-sm my-auto" style="max-width: 240px;">
                                <img src="{{ asset('images/qr-aba.png') }}" alt="ABA QR Code" class="img-fluid rounded">
                            </div>
                            <div class="mt-3 text-muted small">
                                <div><strong>Resort:</strong> Beach Resort Management</div>
                                <div><strong>Ref:</strong> Booking #{{ $booking->id }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-4">
                    <h3 class="h5 mb-3"><i class="bi bi-receipt me-2"></i>Payment Confirmation</h3>

                    @if($booking->paymentSlip)
                        <div class="alert {{ $booking->paymentSlip->reviewed ? 'alert-success' : 'alert-info' }} d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <strong>{{ $booking->paymentSlip->original_filename }}</strong>
                                    @if($booking->paymentSlip->reviewed)
                                        <span class="badge bg-success">Verified by Hotel</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Under Review</span>
                                    @endif
                                </div>
                                <small class="text-muted">Uploaded {{ $booking->paymentSlip->created_at->diffForHumans() }} ({{ $booking->paymentSlip->created_at->format('M j, Y g:i A') }})</small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ $booking->paymentSlip->url() }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> View Slip
                                </a>
                                <form method="POST" action="{{ route('customer.bookings.payment-slip.destroy', $booking) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Remove this slip to upload a new one?">
                                        <i class="bi bi-trash me-1"></i> Replace Slip
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ $booking->paymentSlip->url() }}" target="_blank">
                                <img src="{{ $booking->paymentSlip->url() }}" alt="Uploaded Slip Preview" class="img-thumbnail shadow-sm" style="max-height: 240px; object-fit: contain;">
                            </a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('customer.bookings.payment-slip.store', $booking) }}" enctype="multipart/form-data" class="bg-light p-4 rounded-3 border">
                            @csrf
                            <div class="mb-3">
                                <label for="payment_slip" class="form-label fw-semibold">Upload Payment Slip / Screenshot</label>
                                <input type="file" name="payment_slip" id="payment_slip" class="form-control @error('payment_slip') is-invalid @enderror" accept=".jpg,.jpeg,.png" required>
                                @error('payment_slip')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Accepted formats: JPG, PNG. Maximum file size: 5MB. Please ensure the transferred amount and transaction reference are readable.</div>
                            </div>
                            <button type="submit" class="btn btn-hotel">
                                <i class="bi bi-cloud-arrow-up me-1"></i> Submit Payment Slip
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div></div></div></section>
@endsection
