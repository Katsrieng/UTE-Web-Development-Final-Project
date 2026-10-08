<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $term = \App\Support\ListFilters::term($request);
        $users = User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF])
            ->when($term !== '', fn ($query) => $query->where(fn ($search) => $search
                ->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->when(in_array($request->query('role'), [User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF], true), fn ($query) => $query->where('role', $request->query('role')))
            ->when(in_array($request->query('active'), ['1', '0'], true), fn ($query) => $query->where('is_active', $request->query('active')))
            ->latest()->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        // A blank User with sensible defaults so the shared form can be reused.
        $user = new User(['role' => User::ROLE_STAFF, 'is_active' => true]);

        return view('admin.users.create', compact('user'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        User::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        abort_if($user->isCustomer(), 404);
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isCustomer(), 404);
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        // Empty password box = keep the old password
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Safety: an admin cannot demote or deactivate their own account,
        // otherwise the system could end up with no admin at all.
        if ($user->is($request->user())) {
            $data['role'] = $user->role;
            $data['is_active'] = true;
        }

        DB::transaction(function () use ($user, $data) {
            $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $locked = User::lockForUpdate()->findOrFail($user->id);
            if ($locked->isAdmin() && $locked->is_active && ($data['role'] !== User::ROLE_ADMIN || !$data['is_active']) && $admins->count() <= 1) {
                throw \Illuminate\Validation\ValidationException::withMessages(['role'=>'The last active administrator cannot be demoted or deactivated.']);
            }
            $locked->update($data);
        });
        $user->refresh();

        // If the account was just disabled, kick them out of any open sessions.
        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isCustomer(), 404);
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        return DB::transaction(function () use ($user) {
            $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->orderBy('id')->lockForUpdate()->get();
            $user = User::lockForUpdate()->findOrFail($user->id);
            if ($user->isAdmin() && $user->is_active && $admins->count() <= 1) {
                return back()->with('error', 'The last active administrator cannot be deleted.');
            }
            if (DB::table('booking_status_logs')->where('changed_by', $user->id)->exists()
                || DB::table('event_bookings')->where('processed_by', $user->id)->exists()) {
                return back()->with('error', 'This staff account has historical activity and cannot be deleted. Deactivate the account instead.');
            }
            if (DB::table('bookings')->where('user_id', $user->id)->exists()
                || $user->payments()->exists() || $user->eventBookings()->exists() || $user->memberships()->exists()
                || \App\Models\MembershipPurchase::where('user_id', $user->id)->exists()) {
                return back()->with('error', 'This account has linked business records and cannot be deleted. Deactivate the account instead.');
            }
            if ($user->loyaltyAccount()->exists()) {
                return back()->with('error', 'This customer has an audited loyalty account and cannot be deleted.');
            }
            $user->delete();

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User deleted successfully.');
        });
    }
}
