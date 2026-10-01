<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Security: never trust the browser for the role. Public sign-up = customer.
        $data['role'] = User::ROLE_CUSTOMER;

        $user = User::create($data); // password is hashed by the model's cast

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route($user->homeRoute())
            ->with('success', 'Welcome! Your account has been created.');
    }
}
