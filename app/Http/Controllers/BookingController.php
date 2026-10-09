<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $bookings = Booking::with(['user', 'room.roomType', 'paymentSlip'])
            ->withCount(['payments', 'statusLogs'])
            ->when(is_string($request->query('arrival_date')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query('arrival_date')), fn ($query) => $query->whereDate('check_in_date', $request->query('arrival_date')))
            ->when(is_string($request->query('departure_date')) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query('departure_date')), fn ($query) => $query->whereDate('check_out_date', $request->query('departure_date')))
            ->tap(fn ($query) => \App\Support\ListFilters::bookings($query, $request, true))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('bookings.index', compact('bookings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::where('is_active', true)
            ->where('role', 'customer')
            ->orderBy('name')
            ->get();

        $rooms = Room::where('status', 'available')
            ->orderBy('room_number')
            ->get();

        return view('bookings.create', compact('users', 'rooms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        $this->bookings->create($request->validated());

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Booking created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $booking = Booking::with([
            'user',
            'room',
            'bookingPackages.package',
            'statusLogs.changedBy',
            'paymentSlip',
            'payments',
        ])->findOrFail($id);

        return view('bookings.show', compact('booking'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $booking = Booking::findOrFail($id);

        $users = User::where('is_active', true)
            ->where('role', 'customer')
            ->orderBy('name')
            ->get();

        $rooms = Room::orderBy('room_number')->get();

        return view('bookings.edit', compact('booking', 'users', 'rooms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            // Serialize ordinary edits with lifecycle actions so an edit cannot restore stale status or room data.
            $booking = Booking::query()->lockForUpdate()->findOrFail($id);

            $validated = $request->validate([
                'user_id' => [
                    'required', 'integer',
                    Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)->where('is_active', true),
                ],
                'room_id' => 'required|exists:rooms,id',
                'check_in_date' => 'required|date',
                'check_out_date' => 'required|date|after:check_in_date',
                'number_of_guests' => 'required|integer|min:1',
                'status' => ['sometimes', 'required', 'string', Rule::in([$booking->status])],
                'special_request' => 'nullable|string',
            ], [
                'user_id.exists' => 'Please select an active customer for this booking.',
                'status.in' => 'Manage booking status using the actions on Booking Details.',
            ]);

            if ($booking->status === 'Checked In' && (int) $validated['room_id'] !== (int) $booking->room_id) {
                throw ValidationException::withMessages([
                    'room_id' => 'The room cannot be changed while this booking is Checked In.',
                ]);
            }

            $payments = $booking->payments()->lockForUpdate()->get();
            if ($payments->isNotEmpty() && (int) $validated['user_id'] !== (int) $booking->user_id) {
                throw ValidationException::withMessages(['user_id' => 'A booking with a payment record cannot be reassigned to another customer.']);
            }

            $room = $this->bookings->validateRoom(array_merge($validated, ['status' => $booking->status]), $booking);
            unset($validated['status']);

            $subtotal = $this->bookings->combineTotal($this->bookings->calculateTotal($room, $validated['check_in_date'], $validated['check_out_date']), $booking->packageTotalCents());
            $validated = array_merge($validated, $this->bookings->applyMembershipDiscount($subtotal, $booking->membership_discount_percentage ?? '0.00'));

            if ($payments->contains('status', 'Paid') && (int) round($validated['total_amount'] * 100) !== (int) round((float) $booking->total_amount * 100)) {
                throw ValidationException::withMessages(['payment' => 'The total of a paid booking cannot be changed. Resolve its payment with the hotel first.']);
            }

            $booking->update($validated);
            foreach ($payments->where('status', 'Pending') as $payment) {
                $payment->update(['amount' => $booking->total_amount]);
            }

            return redirect()
                ->route('bookings.index')
                ->with('success', 'Booking updated successfully.');
        });
    }

    public function confirm(Request $request, string $id): RedirectResponse
    {
        return $this->transition($request, $id, 'Confirmed');
    }

    public function cancel(Request $request, string $id): RedirectResponse
    {
        return $this->transition($request, $id, 'Cancelled');
    }

    public function checkIn(Request $request, string $id): RedirectResponse
    {
        return $this->transition($request, $id, 'Checked In');
    }

    public function checkOut(Request $request, string $id): RedirectResponse
    {
        return $this->transition($request, $id, 'Checked Out');
    }

    private function transition(Request $request, string $id, string $newStatus): RedirectResponse
    {
        $booking = $this->bookings->transition($id, $newStatus, $request->user());

        $messages = [
            'Confirmed' => 'Booking confirmed successfully.',
            'Cancelled' => 'Booking cancelled successfully.',
            'Checked In' => 'Guest checked in successfully.',
            'Checked Out' => 'Guest checked out successfully. The room now requires cleaning.',
        ];

        return redirect()
            ->route('bookings.show', $booking)
            ->with('success', $messages[$newStatus]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            $booking = Booking::lockForUpdate()->findOrFail($id);
            if ($booking->payments()->exists() || $booking->statusLogs()->exists()) {
                throw ValidationException::withMessages([
                    'booking' => 'Bookings with payment or status history cannot be deleted.',
                ]);
            }
            $booking->delete();
        });

        return redirect()
            ->route('bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }
}
