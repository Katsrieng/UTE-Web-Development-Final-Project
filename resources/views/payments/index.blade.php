<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Payments</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

@include('partials.navbar')

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

    <h1>Payments</h1>

    <a href="{{ route('payments.create') }}"
       class="btn btn-primary">
        Add Payment
    </a>

</div>


    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif


    <div class="card">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead>

                        <tr>
                            <th>ID</th>
                            <th>Reference</th>
                            <th>User ID</th>
                            <th>Booking ID</th>
                            <th>Event Booking ID</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($payments as $payment)

                            <tr>

                                <td>{{ $payment->id }}</td>

                                <td>
                                    {{ $payment->reference_number }}
                                </td>

                                <td>
                                    {{ $payment->user_id }}
                                </td>

                                <td>
                                    {{ $payment->booking_id ?? '-' }}
                                </td>

                                <td>
                                    {{ $payment->event_booking_id ?? '-' }}
                                </td>

                                <td>
                                    ${{ number_format($payment->amount, 2) }}
                                </td>

                                <td>
                                    {{ $payment->payment_method }}
                                </td>

                                <td>
                                    {{ $payment->payment_date }}
                                </td>

                                <td>
                                    {{ $payment->status }}
                                </td>

                                <td>

                                    <a
                                        href="{{ route('payments.show', $payment) }}"
                                        class="btn btn-sm btn-info">
                                        View
                                    </a>

                                    <a
                                        href="{{ route('payments.edit', $payment) }}"
                                        class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form
                                        action="{{ route('payments.destroy', $payment) }}"
                                        method="POST"
                                        class="d-inline">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this payment?')">

                                            Delete

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="10" class="text-center">

                                    No payments found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{ $payments->links() }}

        </div>

    </div>

</div>

</body>

</html>
