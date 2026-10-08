<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Support\PortalRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        return $this->authenticate($request, ['customer']);
    }

    public function staffCreate()
    {
        return view('auth.staff-login');
    }

    public function staffStore(LoginRequest $request): RedirectResponse
    {
        return $this->authenticate($request, ['admin', 'manager', 'staff']);
    }

    private function authenticate(LoginRequest $request, array $roles): RedirectResponse
    {
        $credentials = $request->validated();
        $credentials['role'] = $roles;
        $credentials['is_active'] = true;
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $user = Auth::user();

        // Correct password, but an admin has disabled this account.
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'Your account has been deactivated. Please contact the hotel administrator.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate(); // protects against session fixation

        return PortalRedirect::afterLogin($request, $user);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $portal = $request->user()?->hasRole('admin', 'manager', 'staff') ? 'staff.login' : 'login';
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route($portal)
            ->with('success', 'You have been logged out.');
    }
}
