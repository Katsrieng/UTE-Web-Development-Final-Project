<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Details</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>Payment Details</h1>

        <a href="{{ route('payments.index') }}"
           class="btn btn-secondary">
            Back
        </a>

    </div>


    <div class="card">

        <div class="card-body">

            <p>
                <strong>ID:</strong>
                {{ $payment->id }}
            </p>

            <p>
                <strong>Reference Number:</strong>
                {{ $payment->reference_number }}
            </p>

            <p>
                <strong>User ID:</strong>
                {{ $payment->user_id }}
            </p>

            <p>
                <strong>Booking ID:</strong>
                {{ $payment->booking_id ?? '-' }}
            </p>

            <p>
                <strong>Event Booking ID:</strong>
                {{ $payment->event_booking_id ?? '-' }}
            </p>

            <p>
                <strong>Amount:</strong>
                ${{ number_format($payment->amount, 2) }}
            </p>

            <p>
                <strong>Payment Method:</strong>
                {{ $payment->payment_method }}
            </p>

            <p>
                <strong>Payment Date:</strong>
                {{ $payment->payment_date }}
            </p>

            <p>
                <strong>Status:</strong>
                {{ $payment->status }}
            </p>


            <a
                href="{{ route('payments.edit', $payment) }}"
                class="btn btn-warning">

                Edit Payment

            </a>
            <a
                href="{{ route('payments.receipt', $payment) }}"
                class="btn btn-success">
                Receipt

            </a>

        </div>

    </div>

</div>

</body>
</html>
