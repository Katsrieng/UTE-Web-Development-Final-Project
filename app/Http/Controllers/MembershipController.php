<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\MembershipType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            ->latest('end_date')
            ->first();

        $currentMembership?->refreshExpiry();

        return view('memberships.index', compact('membershipTypes', 'currentMembership'));
    }

    /**
     * Subscribe the logged-in customer to a membership type.
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'membership_type_id' => 'required|exists:membership_types,id',
        ]);

        $existingActive = Membership::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->first();

        if ($existingActive) {
            return back()->with('error', 'You already have an active membership.');
        }

        $membershipType = MembershipType::findOrFail($validated['membership_type_id']);

        Membership::create([
            'user_id' => Auth::id(),
            'membership_type_id' => $membershipType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths($membershipType->duration_months)->toDateString(),
            'status' => 'active',
        ]);

        return redirect()
            ->route('memberships.index')
            ->with('success', "You're now a {$membershipType->name} member!");
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
