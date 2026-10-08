<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCustomerAccountRequest;
use App\Models\MembershipPurchase;
use App\Models\User;
use App\Support\ListFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $term = ListFilters::term($request);
        $customers = User::query()->where('role', User::ROLE_CUSTOMER)
            ->when($term !== '', fn ($query) => $query->where(fn ($search) => $search
                ->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->when(in_array($request->query('active'), ['0', '1'], true), fn ($query) => $query->where('is_active', $request->query('active')))
            ->withCount('bookings')->latest()->paginate(10)->withQueryString();

        return view('management.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        $this->ensureCustomer($customer);
        $customer->loadCount(['bookings', 'payments', 'eventBookings']);
        return view('management.customers.show', compact('customer'));
    }

    public function edit(User $customer)
    {
        $this->ensureCustomer($customer);
        return view('management.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerAccountRequest $request, User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);
        $customer->update($request->validated());
        if (! $customer->is_active) {
            DB::table('sessions')->where('user_id', $customer->id)->delete();
        }
        return redirect()->route('management.customers.show', $customer)->with('success', 'Customer account updated.');
    }

    public function destroy(User $customer): RedirectResponse
    {
        $this->ensureCustomer($customer);
        return DB::transaction(function () use ($customer) {
            $customer = User::where('role', User::ROLE_CUSTOMER)->lockForUpdate()->findOrFail($customer->id);
            if (DB::table('booking_status_logs')->where('changed_by', $customer->id)->exists()
                || DB::table('event_bookings')->where('processed_by', $customer->id)->exists()
                || $customer->bookings()->exists() || $customer->payments()->exists()
                || $customer->eventBookings()->exists() || $customer->memberships()->exists()
                || MembershipPurchase::where('user_id', $customer->id)->exists()
                || $customer->loyaltyAccount()->exists()) {
                return back()->with('error', 'This customer has linked business history and cannot be deleted. Deactivate the account instead.');
            }
            $customer->delete();
            return redirect()->route('management.customers.index')->with('success', 'Customer account deleted.');
        });
    }

    private function ensureCustomer(User $customer): void
    {
        abort_unless($customer->isCustomer(), 404);
    }
}
