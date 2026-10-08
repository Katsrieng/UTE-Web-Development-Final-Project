<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Room;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function create(Room $room): View
    {
        $room->load('roomType');

        return view('customer.bookings.create', ['room' => $room, 'packages' => Package::active()->orderBy('name')->get()]);
    }

    public function availability(StoreBookingRequest $request, Room $room): View|RedirectResponse
    {
        $reservation = $request->validated();
        try {
            $room = $this->bookings->validateRoom($reservation);
            $quote = $this->bookings->quotePackages($reservation['packages'] ?? [], (int) $reservation['number_of_guests']);
            $roomTotal = $this->bookings->calculateTotal($room, $reservation['check_in_date'], $reservation['check_out_date']);
            $total = $this->bookings->combineTotal($roomTotal, $quote['total_cents']);
            $membershipPricing = $this->bookings->quoteMembershipDiscount($request->user()->id, $total);
        } catch (ValidationException $exception) {
            return redirect()->route('customer.bookings.create', $room)
                ->withErrors($exception->errors())->withInput($request->only([
                    'check_in_date', 'check_out_date', 'number_of_guests', 'special_request', 'packages',
                ]));
        }

        return view('customer.bookings.create', [
            'room' => $room,
            'reservation' => $reservation,
            'numberOfNights' => Carbon::parse($reservation['check_in_date'])->diffInDays(Carbon::parse($reservation['check_out_date'])),
            'totalAmount' => $membershipPricing['total_amount'],
            'subtotal' => $total,
            'membershipPricing' => $membershipPricing,
            'roomTotal' => $roomTotal,
            'packageLines' => $quote['lines'],
            'packageTotal' => $quote['total_cents'] / 100,
            'packages' => Package::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBookingRequest $request, Room $room): RedirectResponse
    {
        try {
            $booking = $this->bookings->create($request->validated());
        } catch (ValidationException $exception) {
            return redirect()->route('customer.bookings.create', $room)
                ->withErrors($exception->errors())->withInput($request->only([
                    'check_in_date', 'check_out_date', 'number_of_guests', 'special_request', 'packages',
                ]));
        }

        return redirect()->route('customer.bookings.show', $booking)
            ->with('success', 'Your booking request has been received.');
    }

    public function index(Request $request): View
    {
        $bookings = Booking::with(['room.roomType', 'payments'])->where('user_id', $request->user()->id)
            ->latest()->paginate(10);

        return view('customer.bookings.index', compact('bookings'));
    }

    public function show(Request $request, string $booking): View
    {
        $booking = Booking::with(['room.roomType', 'bookingPackages.package', 'payments'])->where('user_id', $request->user()->id)->findOrFail($booking);
        $numberOfNights = Carbon::parse($booking->check_in_date)->diffInDays(Carbon::parse($booking->check_out_date));

        return view('customer.bookings.show', compact('booking', 'numberOfNights'));
    }

    public function cancel(Request $request, string $booking): RedirectResponse
    {
        try {
            $booking = $this->bookings->transition($booking, 'Cancelled', $request->user());
        } catch (ValidationException $exception) {
            return redirect()->route('customer.bookings.show', $booking)->withErrors($exception->errors());
        }

        return redirect()->route('customer.bookings.show', $booking)
            ->with('success', 'Your booking has been cancelled.');
    }
}
