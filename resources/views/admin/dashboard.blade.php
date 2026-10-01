<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>Dashboard</h1>

        <a href="{{ route('payments.index') }}"
           class="btn btn-primary">
            Manage Payments
        </a>

    </div>


    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Total Revenue</h6>
                    <h3>${{ number_format($totalRevenue, 2) }}</h3>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Total Payments</h6>
                    <h3>{{ $totalPayments }}</h3>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Paid Payments</h6>
                    <h3>{{ $paidPayments }}</h3>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Pending Payments</h6>
                    <h3>{{ $pendingPayments }}</h3>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Refunded Payments</h6>
                    <h3>{{ $refundedPayments }}</h3>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Room Booking Revenue</h6>
                    <h3>${{ number_format($roomBookingRevenue, 2) }}</h3>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6>Event Booking Revenue</h6>
                    <h3>${{ number_format($eventBookingRevenue, 2) }}</h3>
                </div>
            </div>
        </div>

    </div>

<div class="card mb-4">

    <div class="card-header">
        Payment Status
    </div>

    <div class="card-body">

        <div style="max-width: 500px; margin: auto;">
            <canvas id="paymentStatusChart"></canvas>
        </div>

    </div>

</div>

    <div class="card">

        <div class="card-header">
            Recent Payments
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($recentPayments as $payment)

                            <tr>
                                <td>{{ $payment->reference_number }}</td>
                                <td>${{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->payment_method }}</td>
                                <td>{{ $payment->status }}</td>
                                <td>{{ $payment->payment_date }}</td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="5" class="text-center">
                                    No payments found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>
<script>
    const ctx = document.getElementById('paymentStatusChart');

    new Chart(ctx, {
        type: 'doughnut',

        data: {
            labels: ['Paid', 'Pending', 'Refunded'],

            datasets: [{
                data: [
                    {{ $paidPayments }},
                    {{ $pendingPayments }},
                    {{ $refundedPayments }}
                ]
            }]
        }
    });
</script>
</body>
</html>
