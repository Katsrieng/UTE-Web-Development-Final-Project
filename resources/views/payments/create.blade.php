<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Payment</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Add Payment</h1>

        <a href="{{ route('payments.index') }}"
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

            <form action="{{ route('payments.store') }}" method="POST">

                @csrf


                <div class="mb-3">

                    <label class="form-label">
                        User ID
                    </label>

                    <input
                        type="number"
                        name="user_id"
                        class="form-control"
                        value="{{ old('user_id') }}"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Booking ID
                    </label>

                    <input
                        type="number"
                        name="booking_id"
                        class="form-control"
                        value="{{ old('booking_id') }}"
                    >

                    <small class="text-muted">
                        Leave empty if this payment is for an event booking.
                    </small>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Event Booking ID
                    </label>

                    <input
                        type="number"
                        name="event_booking_id"
                        class="form-control"
                        value="{{ old('event_booking_id') }}"
                    >

                    <small class="text-muted">
                        Leave empty if this payment is for a room booking.
                    </small>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Amount
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        name="amount"
                        class="form-control"
                        value="{{ old('amount') }}"
                        required
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Payment Method
                    </label>

                    <select
                        name="payment_method"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select payment method
                        </option>

                        <option value="Cash">
                            Cash
                        </option>

                        <option value="Card">
                            Card
                        </option>

                        <option value="Bank Transfer">
                            Bank Transfer
                        </option>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Payment Date
                    </label>

                    <input
                        type="date"
                        name="payment_date"
                        class="form-control"
                        value="{{ old('payment_date') }}"
                        required
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

                        <option value="">
                            Select status
                        </option>

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="Paid">
                            Paid
                        </option>

                        <option value="Refunded">
                            Refunded
                        </option>

                    </select>

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Reference Number
                    </label>

                    <input
                        type="text"
                        name="reference_number"
                        class="form-control"
                        value="{{ old('reference_number') }}"
                        placeholder="Example: PAY-001"
                        required
                    >

                </div>


                <button type="submit" class="btn btn-primary">
                    Save Payment
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>
