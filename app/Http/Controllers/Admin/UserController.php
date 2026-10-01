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
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim($request->query('search'));

                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->query('role')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

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
        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
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

        $user->update($data);

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
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
