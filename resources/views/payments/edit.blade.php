<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Payment</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Edit Payment</h1>

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

            <form
                action="{{ route('payments.update', $payment) }}"
                method="POST"
            >

                @csrf
                @method('PUT')


                <div class="mb-3">

                    <label class="form-label">
                        User ID
                    </label>

                    <input
                        type="number"
                        name="user_id"
                        class="form-control"
                        value="{{ old('user_id', $payment->user_id) }}"
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
                        value="{{ old('booking_id', $payment->booking_id) }}"
                    >

                </div>


                <div class="mb-3">

                    <label class="form-label">
                        Event Booking ID
                    </label>

                    <input
                        type="number"
                        name="event_booking_id"
                        class="form-control"
                        value="{{ old('event_booking_id', $payment->event_booking_id) }}"
                    >

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
                        value="{{ old('amount', $payment->amount) }}"
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

                        <option value="Cash"
                            {{ old('payment_method', $payment->payment_method) === 'Cash' ? 'selected' : '' }}>
                            Cash
                        </option>

                        <option value="Card"
                            {{ old('payment_method', $payment->payment_method) === 'Card' ? 'selected' : '' }}>
                            Card
                        </option>

                        <option value="Bank Transfer"
                            {{ old('payment_method', $payment->payment_method) === 'Bank Transfer' ? 'selected' : '' }}>
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
                        value="{{ old('payment_date', $payment->payment_date) }}"
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

                        <option value="Pending"
                            {{ old('status', $payment->status) === 'Pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="Paid"
                            {{ old('status', $payment->status) === 'Paid' ? 'selected' : '' }}>
                            Paid
                        </option>

                        <option value="Refunded"
                            {{ old('status', $payment->status) === 'Refunded' ? 'selected' : '' }}>
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
                        value="{{ old('reference_number', $payment->reference_number) }}"
                        required
                    >

                </div>


                <button type="submit" class="btn btn-primary">
                    Update Payment
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>
