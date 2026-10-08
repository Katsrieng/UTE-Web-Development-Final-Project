<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingPaymentSlipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentSlipController extends Controller
{
    public function __construct(private BookingPaymentSlipService $slips) {}
    /**
     * Store or replace the customer's payment slip for a booking.
     */
    public function store(Request $request, string $booking): RedirectResponse
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->whereIn('status', ['Pending', 'Confirmed', 'Checked In'])
            ->findOrFail($booking);

        $request->validate(['payment_slip' => BookingPaymentSlipService::rules()]);
        DB::transaction(fn () => $this->slips->replace($booking, $request->file('payment_slip')));

        return redirect()
            ->route('customer.payments.booking', $booking)
            ->with('success', 'Payment slip uploaded. Our staff will verify it shortly.');
    }

    /**
     * Delete the customer's payment slip (allows re-upload).
     */
    public function destroy(Request $request, string $booking): RedirectResponse
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->whereIn('status', ['Pending', 'Confirmed', 'Checked In'])
            ->findOrFail($booking);

        $slip = $booking->paymentSlip;

        if (! $slip) {
            return redirect()
                ->route('customer.payments.booking', $booking)
                ->with('error', 'No payment slip found.');
        }

        DB::transaction(fn () => $this->slips->delete($slip));

        return redirect()
            ->route('customer.payments.booking', $booking)
            ->with('success', 'Payment slip removed. You can now upload a new one.');
    }
}
