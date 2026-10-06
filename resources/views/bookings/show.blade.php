<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking Details</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>Booking Details</h1>

        <div>
            <a href="{{ route('bookings.edit', $booking) }}"
               class="btn btn-warning">
                Edit
            </a>

            <a href="{{ route('bookings.index') }}"
               class="btn btn-secondary">
                Back
            </a>
        </div>

    </div>

    @if(session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach([
            'Confirmed' => ['confirm', 'Confirm Booking', 'btn-success'],
            'Cancelled' => ['cancel', 'Cancel Booking', 'btn-danger'],
            'Checked In' => ['check-in', 'Check In', 'btn-primary'],
            'Checked Out' => ['check-out', 'Check Out', 'btn-primary'],
        ] as $status => [$action, $label, $buttonClass])
            @if($booking->canTransitionTo($status))
                <form action="{{ route('bookings.'.$action, $booking) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $buttonClass }}">{{ $label }}</button>
                </form>
            @endif
        @endforeach
    </div>


    <div class="card mb-4">

        <div class="card-body">

            <h5 class="card-title mb-4">
                Booking #{{ $booking->id }}
            </h5>

            <p>
                <strong>Customer:</strong>
                {{ $booking->user->name }}
            </p>

            <p>
                <strong>Room:</strong>
                {{ $booking->room->room_number }}
            </p>

            <p>
                <strong>Check-in Date:</strong>
                {{ $booking->check_in_date }}
            </p>

            <p>
                <strong>Check-out Date:</strong>
                {{ $booking->check_out_date }}
            </p>

            <p>
                <strong>Number of Guests:</strong>
                {{ $booking->number_of_guests }}
            </p>

            <p>
                <strong>Total Amount:</strong>
                ${{ number_format($booking->total_amount, 2) }}
            </p>
            @include('bookings._package-summary')

            <p>
                <strong>Status:</strong>
                {{ $booking->status }}
            </p>

            <p>
                <strong>Special Request:</strong>
                {{ $booking->special_request ?? 'None' }}
            </p>

        </div>

    </div>


    <div class="card">

        <div class="card-body">

            <h5 class="card-title mb-3">
                Status History
            </h5>

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>

                        <tr>
                            <th>Old Status</th>
                            <th>New Status</th>
                            <th>Changed By</th>
                            <th>Note</th>
                            <th>Date</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($booking->statusLogs as $log)

                            <tr>

                                <td>
                                    {{ $log->old_status ?? '-' }}
                                </td>

                                <td>
                                    {{ $log->new_status }}
                                </td>

                                <td>
                                    {{ $log->changedBy->name }}
                                </td>

                                <td>
                                    {{ $log->note ?? '-' }}
                                </td>

                                <td>
                                    {{ $log->created_at }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="5" class="text-center">
                                    No status changes recorded.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>
