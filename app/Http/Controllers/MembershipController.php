<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MembershipController extends Controller
{
    /** Payment methods a customer can use online. */
    private const PAYMENT_METHODS = ['Card', 'Bank Transfer'];

    /**
     * Show available membership types + the customer's current membership, if any.
     */
    public function index()
    {
        $membershipTypes = MembershipType::active()->get();

        $currentMembership = Membership::with(['membershipType', 'payment'])
            ->where('user_id', Auth::id())
            ->latest('end_date')
            ->latest('id')
            ->first();

        $currentMembership?->refreshExpiry();

        return view('memberships.index', compact('membershipTypes', 'currentMembership'));
    }

    /**
     * Payment page shown before the membership is created.
     */
    public function checkout(MembershipType $membershipType)
    {
        abort_unless($membershipType->status === 'active', 404);

        if ($this->activeMembershipFor(Auth::id())) {
            return redirect()
                ->route('memberships.index')
                ->with('error', 'You already have an active membership.');
        }

        return view('memberships.checkout', [
            'membershipType' => $membershipType,
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    /**
     * Pay for a membership type and activate it for the logged-in customer.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'membership_type_id' => 'required|exists:membership_types,id',
            'payment_method' => 'required|in:'.implode(',', self::PAYMENT_METHODS),
        ]);

        $membershipType = MembershipType::active()->findOrFail($validated['membership_type_id']);
        $userId = Auth::id();

        $membership = DB::transaction(function () use ($membershipType, $validated, $userId) {
            // Re-check inside the transaction so a double-click can't create two memberships.
            if ($this->activeMembershipFor($userId, lock: true)) {
                return null;
            }

            $membership = Membership::create([
                'user_id' => $userId,
                'membership_type_id' => $membershipType->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addMonths($membershipType->duration_months)->toDateString(),
                'status' => 'active',
            ]);

            // Free tiers don't need a payment record.
            if ((float) $membershipType->price > 0) {
                Payment::create([
                    'user_id' => $userId,
                    'membership_id' => $membership->id,
                    'amount' => $membershipType->price,
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => now()->toDateString(),
                    'status' => 'Paid',
                    'reference_number' => 'MEM-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                ]);
            }

            return $membership;
        });

        if (! $membership) {
            return redirect()
                ->route('memberships.index')
                ->with('error', 'You already have an active membership.');
        }

        return redirect()
            ->route('memberships.index')
            ->with('success', "Payment received. You're now a {$membershipType->name} member!");
    }

    /**
     * Cancel the customer's active membership.
     */
    public function cancel(Membership $membership)
    {
        abort_unless($membership->user_id === Auth::id(), 403);

        if ($membership->status !== 'active') {
            return redirect()
                ->route('memberships.index')
                ->with('error', 'This membership is not active.');
        }

        $membership->update(['status' => 'cancelled']);

        return redirect()
            ->route('memberships.index')
            ->with('success', 'Membership cancelled.');
    }

    private function activeMembershipFor(int $userId, bool $lock = false): ?Membership
    {
        $query = Membership::where('user_id', $userId)
            ->where('status', 'active')
            ->where('end_date', '>=', now()->toDateString());

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
