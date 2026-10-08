<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPaymentSlip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentSlipController extends Controller
{
    /**
     * Store or replace the customer's payment slip for a booking.
     */
    public function store(Request $request, string $booking): RedirectResponse
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->whereIn('status', ['Pending', 'Confirmed', 'Checked In'])
            ->findOrFail($booking);

        $request->validate([
            'payment_slip' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120', // 5 MB
            ],
        ]);

        // Delete the old slip file if it exists.
        if ($existing = $booking->paymentSlip) {
            Storage::disk('public')->delete($existing->file_path);
            $existing->delete();
        }

        $file = $request->file('payment_slip');
        $path = $file->storeAs(
            'payment-slips',
            'booking-' . $booking->id . '-' . time() . '.' . $file->extension(),
            'public'
        );

        BookingPaymentSlip::create([
            'booking_id'        => $booking->id,
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type'         => $file->getMimeType(),
            'reviewed'          => false,
        ]);

        return redirect()
            ->route('customer.bookings.show', $booking)
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
                ->route('customer.bookings.show', $booking)
                ->with('error', 'No payment slip found.');
        }

        Storage::disk('public')->delete($slip->file_path);
        $slip->delete();

        return redirect()
            ->route('customer.bookings.show', $booking)
            ->with('success', 'Payment slip removed. You can now upload a new one.');
    }
}
