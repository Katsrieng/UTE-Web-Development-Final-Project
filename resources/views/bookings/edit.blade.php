<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Booking</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>Edit Booking</h1>

        <a href="{{ route('bookings.index') }}"
           class="btn btn-secondary">
            Back
        </a>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <ul class="mb-0">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <div class="card">

        <div class="card-body">

            <form action="{{ route('bookings.update', $booking) }}" method="POST">

                @csrf
                @method('PUT')


                <div class="mb-3">

                    <label class="form-label">
                        Customer
                    </label>

                    <select
                        name="user_id"
                        class="form-select"
                        required
                    >

                        @foreach($users as $user)

                            <option
                                value="{{ $user->id }}"
                                {{ old('user_id', $booking->user_id) == $user->id ? 'selected' : '' }}
                            >
                                {{ $user->name }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Room
                    </label>

                    <select
                        name="room_id"
                        class="form-select"
                        required
                    >

                        @foreach($rooms as $room)

                            <option
                                value="{{ $room->id }}"
                                data-price="{{ $room->price_per_night }}"
                                {{ old('room_id', $booking->room_id) == $room->id ? 'selected' : '' }}
                            >
                                Room {{ $room->room_number }}
                                - ${{ number_format($room->price_per_night, 2) }}/night
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Check-in Date
                    </label>

                    <input
                        type="date"
                        name="check_in_date"
                        class="form-control"
                        value="{{ old('check_in_date', $booking->check_in_date) }}"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Check-out Date
                    </label>

                    <input
                        type="date"
                        name="check_out_date"
                        class="form-control"
                        value="{{ old('check_out_date', $booking->check_out_date) }}"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Number of Guests
                    </label>

                    <input
                        type="number"
                        name="number_of_guests"
                        class="form-control"
                        value="{{ old('number_of_guests', $booking->number_of_guests) }}"
                        min="1"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Total Amount
                    </label>

                    <input
                        type="text"
                        id="total_amount"
                        class="form-control"
                        value="${{ number_format($booking->total_amount, 2) }}"
                        readonly
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                        required
                    >

                        @foreach([
                            'Pending',
                            'Confirmed',
                            'Checked In',
                            'Checked Out',
                            'Cancelled'
                        ] as $status)

                            <option
                                value="{{ $status }}"
                                {{ old('status', $booking->status) == $status ? 'selected' : '' }}
                            >
                                {{ $status }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Special Request
                    </label>

                    <textarea
                        name="special_request"
                        class="form-control"
                        rows="3"
                    >{{ old('special_request', $booking->special_request) }}</textarea>

                </div>


                <button type="submit" class="btn btn-primary">
                    Update Booking
                </button>

            </form>

        </div>

    </div>

</div>

<script>
    const roomSelect = document.querySelector('select[name="room_id"]');
    const checkInInput = document.querySelector('input[name="check_in_date"]');
    const checkOutInput = document.querySelector('input[name="check_out_date"]');
    const totalAmountInput = document.getElementById('total_amount');

    function calculateTotal() {
        const selectedRoom = roomSelect.options[roomSelect.selectedIndex];
        const pricePerNight = parseFloat(selectedRoom.dataset.price);

        const checkIn = new Date(checkInInput.value);
        const checkOut = new Date(checkOutInput.value);

        if (!pricePerNight || !checkInInput.value || !checkOutInput.value) {
            totalAmountInput.value = '$0.00';
            return;
        }

        const difference = checkOut - checkIn;
        const numberOfNights = difference / (1000 * 60 * 60 * 24);

        if (numberOfNights <= 0) {
            totalAmountInput.value = '$0.00';
            return;
        }

        const total = pricePerNight * numberOfNights;

        totalAmountInput.value = '$' + total.toFixed(2);
    }

    roomSelect.addEventListener('change', calculateTotal);
    checkInInput.addEventListener('change', calculateTotal);
    checkOutInput.addEventListener('change', calculateTotal);

    calculateTotal();
</script>

</body>
</html>
