<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\MembershipType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class MembershipController extends Controller
{
    /**
     * Show available membership types + the customer's current membership, if any.
     */
    public function index()
    {
        $membershipTypes = MembershipType::active()->get();

        $currentMembership = Membership::with('membershipType')
            ->where('user_id', Auth::id())
            ->current()
            ->orderByDesc('start_date')->orderByDesc('id')
            ->first();

        $pendingPurchase = \App\Models\MembershipPurchase::with('payment')->where('user_id', Auth::id())->where('status', 'pending')->first();
        return view('memberships.index', compact('membershipTypes', 'currentMembership', 'pendingPurchase'));
    }

    /**
     * Paid membership activation awaits the Payment integration. Retain the route safely.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'membership_type_id' => ['required', 'integer', Rule::exists('membership_types', 'id')->where('status', 'active')],
        ]);

        // No inserts means repeated or concurrent requests cannot create free/overlapping memberships.
        return redirect()->route('memberships.index')->with('error', 'Membership purchase is not available yet. No membership has been activated.');
    }

    /**
     * Cancel the customer's active membership.
     */
    public function cancel(Membership $membership)
    {
        abort_unless($membership->user_id === Auth::id(), 403);

        $membership->update(['status' => 'cancelled']);

        return redirect()
            ->route('memberships.index')
            ->with('success', 'Membership cancelled.');
    }
}
