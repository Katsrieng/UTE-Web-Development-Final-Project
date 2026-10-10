<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Booking;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;
use App\Models\MembershipPurchase;
use App\Notifications\PaymentStatusUpdatedNotification;
use App\Services\PaymentService;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}
    public function index(Request $request)
    {
        $payments = Payment::with(['user', 'eventBooking.user', 'eventBooking.venue', 'booking.room', 'membershipPurchase'])
            ->tap(fn ($query) => \App\Support\ListFilters::payments($query, $request))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        $users = User::where('role', User::ROLE_CUSTOMER)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $eventBookings = EventBooking::with(['user', 'venue'])->latest()->get();
        $roomBookings = Booking::with(['user', 'room'])->latest()->get();

        return view('payments.create', compact('users', 'eventBookings', 'roomBookings'));
    }

    public function store(StorePaymentRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            if ($data['status'] === 'Paid') { abort_unless($request->user()->hasPermission('verify_payments'), 403); }
            if (! empty($data['membership_purchase_id'])) {
                throw ValidationException::withMessages(['membership_purchase_id' => 'Membership payments must originate from a customer purchase.']);
            }
            if (! empty($data['booking_id'])) {
                User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
                $booking = Booking::lockForUpdate()->findOrFail($data['booking_id']);
                if ((int) $booking->user_id !== (int) $data['user_id']) {
                    throw ValidationException::withMessages(['user_id' => 'Payment customer must match the booking.']);
                }
                $this->payments->checkBooking($booking);
                if ($booking->payments()->exists()) {
                    throw ValidationException::withMessages(['booking_id' => 'This booking already has a payment record.']);
                }
                $data['amount'] = $booking->total_amount;
            }
            $status = $data['status'];
            $data['status'] = 'Pending';
            $payment = Payment::create($data);
            if ($status === 'Paid') { $this->payments->verify($payment, $request->user()); }
        });

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment created successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'eventBooking.user', 'eventBooking.venue', 'booking.room', 'membershipPurchase']);

        $loyalty = $payment->user?->isCustomer() ? app(LoyaltyService::class)->summary($payment->user) : null;
        return view('payments.show', compact('payment', 'loyalty'));
    }

    public function edit(Payment $payment)
    {
        if ($payment->status !== 'Pending') {
            return redirect()->route('payments.show', $payment)
                ->with('error', 'Only pending payments can be edited.');
        }

        $users = User::where(function ($query) {
            $query->where('role', User::ROLE_CUSTOMER)
                ->where('is_active', true);
        })
            ->orWhere('id', $payment->user_id)
            ->orderBy('name')
            ->get();
        $eventBookings = EventBooking::with(['user', 'venue'])->latest()->get();
        $roomBookings = Booking::with(['user', 'room'])->latest()->get();

        return view('payments.edit', compact('payment', 'users', 'eventBookings', 'roomBookings'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        return DB::transaction(function () use ($request, $payment) {
            $payment = $this->payments->lockPayment($payment);

            if ($payment->status !== 'Pending') {
                return redirect()->route('payments.show', $payment)
                    ->with('error', 'Only pending payments can be edited.');
            }

            if ($request->validated('status') === 'Refunded') {
                return redirect()->route('payments.show', $payment)
                    ->with('error', 'Only paid payments can be refunded.');
            }

            $data = $request->validated();
            if ($data['status'] === 'Paid') { abort_unless($request->user()->hasPermission('verify_payments'), 403); }
            if ($payment->booking_id || $payment->membership_purchase_id || ! empty($data['booking_id']) || ! empty($data['membership_purchase_id'])) {
                foreach (['user_id', 'booking_id', 'event_booking_id', 'membership_purchase_id'] as $field) {
                    if ((int) ($data[$field] ?? 0) !== (int) ($payment->$field ?? 0)) {
                        throw ValidationException::withMessages([$field => 'A linked payment cannot be reassigned.']);
                    }
                }
                $data['amount'] = $payment->booking_id ? $payment->booking->total_amount : $payment->membershipPurchase->price;
            }
            $status = $data['status'];
            $data['status'] = 'Pending';
            $payment->update($data);
            if ($status === 'Paid') { $this->payments->verify($payment, $request->user()); }

            return redirect()
                ->route('payments.index')
                ->with('success', 'Payment updated successfully.');
        });
    }

    public function destroy(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $payment = $this->payments->lockPayment($payment);

            if ($payment->status !== 'Pending') {
                return redirect()->route('payments.show', $payment)
                    ->with('error', 'Only pending payments can be deleted.');
            }

            if ($payment->membership_purchase_id) { $payment->membershipPurchase->update(['status' => 'cancelled']); }
            $payment->delete();

            return redirect()
                ->route('payments.index')
                ->with('success', 'Payment deleted successfully.');
        });
    }

    public function refund(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $payment = $this->payments->lockPayment($payment);

            if ($payment->status !== 'Paid') {
                return back()->with('error', 'Only paid payments can be refunded.');
            }

            $payment->update(['status' => 'Refunded']);
            $this->payments->refundMembership($payment);
            $this->payments->reverseLoyalty($payment);
            $notification = PaymentStatusUpdatedNotification::forPayment($payment);
            DB::afterCommit(fn () => User::find($payment->user_id)?->notify($notification));

            return back()->with('success', 'Payment marked as refunded.');
        });
    }

    public function verify(\Illuminate\Http\Request $request, Payment $payment)
    {
        $this->payments->verify($payment, $request->user());
        return redirect()->route('payments.show', $payment)->with('success', 'Payment verified and recorded as paid.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['user', 'eventBooking.user', 'eventBooking.venue', 'booking.room', 'membershipPurchase']);

        return view('payments.receipt', compact('payment'));
    }
}
