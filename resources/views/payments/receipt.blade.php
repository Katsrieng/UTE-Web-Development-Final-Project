<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payment Receipt</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

<div class="container mt-5">

    <div class="card">

        <div class="card-body">

            <div class="text-center mb-4">

                <h2>Beach Resort</h2>

                <h4>Payment Receipt</h4>

            </div>


            <hr>


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


            <hr>


            <h4>
                Total:
                ${{ number_format($payment->amount, 2) }}
            </h4>


            <div class="mt-4 no-print">

                <button
                    onclick="window.print()"
                    class="btn btn-primary">

                    Print Receipt

                </button>

                <a
                    href="{{ route('payments.show', $payment) }}"
                    class="btn btn-secondary">

                    Back

                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>
