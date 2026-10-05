<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bookings</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>

<body>

@include('partials.navbar')

<div class="container mt-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h1>Bookings</h1>

        <a href="{{ route('bookings.create') }}"
           class="btn btn-primary">
            Add Booking
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
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Guests</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($bookings as $booking)

                            <tr>

                                <td>{{ $booking->id }}</td>

                                <td>
                                    {{ $booking->user->name }}
                                </td>

                                <td>
                                    {{ $booking->room->room_number }}
                                </td>

                                <td>
                                    {{ $booking->check_in_date }}
                                </td>

                                <td>
                                    {{ $booking->check_out_date }}
                                </td>

                                <td>
                                    {{ $booking->number_of_guests }}
                                </td>

                                <td>
                                    ${{ number_format($booking->total_amount, 2) }}
                                </td>

                                <td>
                                    {{ $booking->status }}
                                </td>

                                <td>

                                    <a
                                        href="{{ route('bookings.show', $booking) }}"
                                        class="btn btn-sm btn-info">
                                        View
                                    </a>

                                    <a
                                        href="{{ route('bookings.edit', $booking) }}"
                                        class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <form
                                        action="{{ route('bookings.destroy', $booking) }}"
                                        method="POST"
                                        class="d-inline">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this booking?')">

                                            Delete

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="9" class="text-center">

                                    No bookings found.

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{ $bookings->links() }}

        </div>

    </div>

</div>

</body>

</html>