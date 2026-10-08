<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Membership;
use App\Models\MembershipPurchase;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private BookingService $bookings) {}

    private function error(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    public function checkBooking(Booking $booking): void
    {
        if (! in_array($booking->status, Booking::ACTIVE_STATUSES, true)) {
            $this->error('payment', 'This booking cannot receive a payment in its current status.');
        }
    }

    public function checkPlan(MembershipType $type): void
    {
        if ($type->status !== 'active' || $type->price === null || (float) $type->price <= 0 || $type->duration_months != 12) {
            $this->error('membership', 'This membership plan is not available for purchase.');
        }
    }

    private function checkNoMembership(int $userId): void
    {
        if (Membership::where('user_id', $userId)->where('status', 'active')->whereDate('end_date', '>=', today())->exists()) {
            $this->error('membership', 'You already have an active or scheduled membership. Paid upgrades and renewals are not available yet.');
        }
    }

    private function createPayment(array $attributes, array $selection): Payment
    {
        // Demo Card is deliberately simulated; no card data is received or persisted.
        return Payment::create(array_merge($attributes, [
            'payment_method' => $selection['payment_method'],
            'status' => $selection['payment_method'] === 'Card' ? 'Paid' : 'Pending',
            'payment_date' => today()->toDateString(),
            'reference_number' => 'PAY-'.strtoupper((string) Str::ulid()),
            'transaction_reference' => $selection['payment_method'] === 'ABA / KHQR' ? $selection['transaction_reference'] : null,
        ]));
    }

    public function payBooking(User $customer, int $bookingId, array $selection): Payment
    {
        return DB::transaction(function () use ($customer, $bookingId, $selection) {
            $booking = Booking::where('user_id', $customer->id)->lockForUpdate()->findOrFail($bookingId);
            $this->checkBooking($booking);
            $existing = $booking->payments()->orderBy('id')->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->user_id !== (int) $customer->id) { $this->error('payment', 'The hotel must review this legacy payment record before checkout.'); }
                return $existing;
            }
            $payment = $this->createPayment(['user_id' => $customer->id, 'booking_id' => $booking->id, 'amount' => $booking->total_amount], $selection);
            if ($payment->status === 'Paid') { $this->applyPaid($payment, $customer); }
            return $payment;
        });
    }

    public function purchaseMembership(User $customer, int $typeId, array $selection): Payment
    {
        return DB::transaction(function () use ($customer, $typeId, $selection) {
            User::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $this->checkNoMembership($customer->id);
            $pending = MembershipPurchase::where('user_id', $customer->id)->where('status', 'pending')->lockForUpdate()->first();
            if ($pending) {
                if ($pending->membership_type_id !== $typeId) { $this->error('membership', 'Please complete or ask the hotel to cancel your existing membership purchase first.'); }
                return $pending->payment()->firstOrFail();
            }
            $type = MembershipType::lockForUpdate()->findOrFail($typeId);
            $this->checkPlan($type);
            $purchase = MembershipPurchase::create(['user_id' => $customer->id, 'membership_type_id' => $type->id,
                'membership_name' => $type->name, 'price' => $type->price, 'purchase_type' => 'new', 'status' => 'pending']);
            $payment = $this->createPayment(['user_id' => $customer->id, 'membership_purchase_id' => $purchase->id, 'amount' => $purchase->price], $selection);
            if ($payment->status === 'Paid') { $this->applyPaid($payment, $customer); }
            return $payment;
        });
    }

    /** Lock parents before payment to serialize customer submits, staff verification and refunds. */
    public function lockPayment(Payment $payment): Payment
    {
        if ($payment->membership_purchase_id) {
            User::whereKey($payment->user_id)->lockForUpdate()->firstOrFail();
            MembershipPurchase::whereKey($payment->membership_purchase_id)->lockForUpdate()->firstOrFail();
        }
        if ($payment->booking_id) { Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail(); }
        return Payment::lockForUpdate()->findOrFail($payment->id);
    }

    private function applyPaid(Payment $payment, User $actor): void
    {
        if ($payment->booking_id) {
            $booking = Booking::findOrFail($payment->booking_id);
            $this->checkBooking($booking);
            if ((int) $payment->user_id !== (int) $booking->user_id) { $this->error('payment', 'Payment customer does not match the booking.'); }
            $this->bookings->confirmPaidBooking($booking, $actor);
        }
        if ($payment->membership_purchase_id) {
            $purchase = MembershipPurchase::findOrFail($payment->membership_purchase_id);
            if ($purchase->status === 'completed') { return; }
            if ($purchase->status !== 'pending') { $this->error('membership', 'This membership purchase cannot be activated.'); }
            $this->checkNoMembership($purchase->user_id);
            $type = MembershipType::lockForUpdate()->findOrFail($purchase->membership_type_id);
            $this->checkPlan($type);
            $membership = Membership::create(['user_id' => $purchase->user_id, 'membership_type_id' => $type->id,
                'start_date' => today()->toDateString(), 'end_date' => today()->addYearNoOverflow()->toDateString(), 'status' => 'active']);
            $purchase->update(['status' => 'completed', 'membership_id' => $membership->id]);
        }
    }

    public function verify(Payment $payment, User $actor): Payment
    {
        return DB::transaction(function () use ($payment, $actor) {
            $payment = $this->lockPayment($payment);
            if ($payment->status === 'Paid') { return $payment; }
            if ($payment->status !== 'Pending') { $this->error('payment', 'Only pending payments can be verified.'); }
            if ($payment->booking_id) { $payment->amount = Booking::findOrFail($payment->booking_id)->total_amount; }
            if ($payment->membership_purchase_id) { $payment->amount = MembershipPurchase::findOrFail($payment->membership_purchase_id)->price; }
            $payment->status = 'Paid';
            $payment->save();
            $this->applyPaid($payment, $actor);
            return $payment;
        });
    }

    public function refundMembership(Payment $payment): void
    {
        if ($payment->membership_purchase_id) {
            $purchase = $payment->membershipPurchase;
            $purchase->membership?->update(['status' => 'cancelled']);
            $purchase->update(['status' => 'refunded']);
        }
    }
}
