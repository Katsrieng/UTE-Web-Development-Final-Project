@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="auth-card">
                    <div class="row g-0">
                        <div class="col-lg-5 d-none d-lg-block">
                            <div class="auth-card-aside">
                                <i class="bi bi-buildings"></i>
                                <h1 class="h2 mt-4">Welcome back</h1>
                                <p class="text-white-50 lh-lg">Sign in to manage your reservations, profile, hotel operations, or event requests.</p>
                                <hr class="border-light border-opacity-25 my-4">
                                <p class="small text-white-50 mb-0">Comfort, service, and hospitality—all in one place.</p>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="auth-card-form">
                                <p class="section-kicker">Account access</p>
                                <h2 class="h2 mb-2">Sign in</h2>
                                <p class="text-muted mb-4">Enter your account details to continue.</p>

                                <form method="POST" action="{{ route('login') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email address</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                                   class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="email">
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password">
                                            <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
                                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                                            <label class="form-check-label" for="remember">Remember me</label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-hotel w-100">Sign in</button>
                                </form>
                                <p class="text-center text-muted mt-4 mb-0">New here? <a href="{{ route('register') }}" class="fw-semibold">Create a customer account</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
