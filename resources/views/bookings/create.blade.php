@extends('layouts.management')

@section('title', 'Add Booking')
@section('page-label', 'Bookings')

@section('content')
<div class="mb-4">

@staffroute('bookings.index')
<a href="{{ route('bookings.index') }}" class="text-decoration-none">
        <i class="bi bi-arrow-left me-1"></i> Back to bookings
    </a>
@endstaffroute

</div>

<div class="page-heading">
    <div>
        <p class="section-kicker">Reservation management</p>
        <h1>Add Booking</h1>
        <p>Create a new room reservation for a registered customer.</p>
    </div>
</div>

@if($errors->any())
    <div class="validation-summary mb-4">
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


@staffroute('bookings.store')
<form action="{{ route('bookings.store') }}" method="POST">
    @csrf

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-4">Reservation Details</h2>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="user_id" class="form-label">Customer</label>
                            <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                <option value="">Select customer</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="room_id" class="form-label">Room</label>
                            <select id="room_id" name="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                                <option value="">Select room</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" data-price="{{ $room->price_per_night }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                        Room {{ $room->room_number }} &mdash; ${{ number_format($room->price_per_night, 2) }}/night
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="check_in_date" class="form-label">Check-in Date</label>
                            <input type="date" id="check_in_date" name="check_in_date" class="form-control @error('check_in_date') is-invalid @enderror" value="{{ old('check_in_date') }}" required>
                            @error('check_in_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="check_out_date" class="form-label">Check-out Date</label>
                            <input type="date" id="check_out_date" name="check_out_date" class="form-control @error('check_out_date') is-invalid @enderror" value="{{ old('check_out_date') }}" required>
                            @error('check_out_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="number_of_guests" class="form-label">Number of Guests</label>
                            <input type="number" id="number_of_guests" name="number_of_guests" class="form-control @error('number_of_guests') is-invalid @enderror" value="{{ old('number_of_guests', 1) }}" min="1" required>
                            @error('number_of_guests')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label for="total_amount" class="form-label">Estimated Total</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" id="total_amount" class="form-control bg-light" value="0.00" readonly>
                            </div>
                            <small class="text-muted">Calculated from room price and stay duration.</small>
                        </div>

                        <div class="col-12">
                            <label for="special_request" class="form-label">Special Request <span class="text-muted">(Optional)</span></label>
                            <textarea id="special_request" name="special_request" class="form-control" rows="3" placeholder="e.g. Early check-in, high floor...">{{ old('special_request') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Booking Summary</h2>
                    <p class="text-muted small mb-4">New bookings are created with <strong>Pending</strong> status by default and can be confirmed after review.</p>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-hotel">
                            <i class="bi bi-check2-circle me-1"></i> Save Booking
                        </button>
                        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endstaffroute


@push('scripts')
<script>
    const roomSelect = document.querySelector('select[name="room_id"]');
    const checkInInput = document.querySelector('input[name="check_in_date"]');
    const checkOutInput = document.querySelector('input[name="check_out_date"]');
    const totalAmountInput = document.getElementById('total_amount');

    function calculateTotal() {
        if (!roomSelect || !checkInInput || !checkOutInput || !totalAmountInput) return;
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        const pricePerNight = parseFloat(selectedRoom ? selectedRoom.dataset.price : 0);

        const checkIn = new Date(checkInInput.value);
        const checkOut = new Date(checkOutInput.value);

        if (!pricePerNight || !checkInInput.value || !checkOutInput.value) {
            totalAmountInput.value = '0.00';
            return;
        }

        const difference = checkOut - checkIn;
        const numberOfNights = difference / (1000 * 60 * 60 * 24);

        if (numberOfNights <= 0) {
            totalAmountInput.value = '0.00';
            return;
        }

        const total = pricePerNight * numberOfNights;
        totalAmountInput.value = total.toFixed(2);
    }

    if (roomSelect) roomSelect.addEventListener('change', calculateTotal);
    if (checkInInput) checkInInput.addEventListener('change', calculateTotal);
    if (checkOutInput) checkOutInput.addEventListener('change', calculateTotal);

    calculateTotal();
</script>
@endpush
@endsection
