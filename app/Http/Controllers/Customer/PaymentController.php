<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPaymentRequest;
use App\Models\Booking;
use App\Models\MembershipPurchase;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function booking(Request $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
        $this->payments->checkBooking($booking);
        if ($payment = $booking->payments()->orderBy('id')->first()) {
            abort_unless($payment->user_id === $request->user()->id, 404);
            return redirect()->route('customer.payments.show', $payment);
        }
        $booking->load(['room.roomType', 'bookingPackages.package']);
        return view('customer.payments.checkout', ['booking' => $booking, 'plan' => null, 'amount' => $booking->total_amount,
            'action' => route('customer.payments.booking.store', $booking)]);
    }

    public function storeBooking(CustomerPaymentRequest $request, Booking $booking)
    {
        abort_unless($booking->user_id === $request->user()->id, 404);
        $payment = $this->payments->payBooking($request->user(), $booking->id, $request->validated());
        return redirect()->route('customer.payments.show', $payment);
    }

    public function membership(Request $request, MembershipType $membershipType)
    {
        $this->payments->checkPlan($membershipType);
        $pending = MembershipPurchase::where('user_id', $request->user()->id)->where('status', 'pending')->first();
        if ($pending?->payment) { return redirect()->route('customer.payments.show', $pending->payment); }
        return view('customer.payments.checkout', ['booking' => null, 'plan' => $membershipType, 'amount' => $membershipType->price,
            'action' => route('customer.payments.membership.store', $membershipType)]);
    }

    public function storeMembership(CustomerPaymentRequest $request, MembershipType $membershipType)
    {
        $payment = $this->payments->purchaseMembership($request->user(), $membershipType->id, $request->validated());
        return redirect()->route('customer.payments.show', $payment);
    }

    public function show(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 404);
        if ($payment->booking_id) { abort_unless($payment->booking?->user_id === $request->user()->id, 404); }
        $payment->load(['booking.room', 'membershipPurchase', 'eventBooking']);
        return view('customer.payments.show', compact('payment'));
    }

    public function receipt(Request $request, Payment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 404);
        if ($payment->booking_id) { abort_unless($payment->booking?->user_id === $request->user()->id, 404); }
        $payment->load(['user', 'booking.room', 'membershipPurchase', 'eventBooking.venue']);
        return view('payments.receipt', ['payment' => $payment, 'customerReceipt' => true]);
    }
}
