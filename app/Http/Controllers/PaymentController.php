<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Booking;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['user', 'eventBooking.user', 'eventBooking.venue'])
            ->latest()
            ->paginate(10);

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
        Payment::create($request->validated());

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment created successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['user', 'eventBooking.user', 'eventBooking.venue']);

        return view('payments.show', compact('payment'));
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
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->status !== 'Pending') {
                return redirect()->route('payments.show', $payment)
                    ->with('error', 'Only pending payments can be edited.');
            }

            if ($request->validated('status') === 'Refunded') {
                return redirect()->route('payments.show', $payment)
                    ->with('error', 'Only paid payments can be refunded.');
            }

            $payment->update($request->validated());

            return redirect()
                ->route('payments.index')
                ->with('success', 'Payment updated successfully.');
        });
    }

    public function destroy(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->status !== 'Pending') {
                return redirect()->route('payments.show', $payment)
                    ->with('error', 'Only pending payments can be deleted.');
            }

            $payment->delete();

            return redirect()
                ->route('payments.index')
                ->with('success', 'Payment deleted successfully.');
        });
    }

    public function refund(Payment $payment)
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->getKey());

            if ($payment->status !== 'Paid') {
                return back()->with('error', 'Only paid payments can be refunded.');
            }

            $payment->update(['status' => 'Refunded']);

            return back()->with('success', 'Payment marked as refunded.');
        });
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['user', 'eventBooking.user', 'eventBooking.venue']);

        return view('payments.receipt', compact('payment'));
    }
}
