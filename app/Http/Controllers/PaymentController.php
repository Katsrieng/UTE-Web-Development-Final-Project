<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;

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

        return view('payments.create', compact('users', 'eventBookings'));
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
        $users = User::where(function ($query) {
            $query->where('role', User::ROLE_CUSTOMER)
                ->where('is_active', true);
        })
            ->orWhere('id', $payment->user_id)
            ->orderBy('name')
            ->get();
        $eventBookings = EventBooking::with(['user', 'venue'])->latest()->get();

        return view('payments.edit', compact('payment', 'users', 'eventBookings'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $payment->update($request->validated());

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return redirect()
            ->route('payments.index')
            ->with('success', 'Payment deleted successfully.');
    }
    public function receipt(Payment $payment)
    {
        $payment->load(['user', 'eventBooking.user', 'eventBooking.venue']);

        return view('payments.receipt', compact('payment'));
    }
}
