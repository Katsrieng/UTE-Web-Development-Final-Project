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
                                <img class="auth-brand-logo" src="{{ asset('images/brand/utopia_bay_logo_wb.svg') }}" alt="Utopia Bay" width="68" height="68">
                                <h1 class="h2 mt-4">Welcome back</h1>
                                <p class="text-white-50 lh-lg">Sign in to manage your Utopia Bay bookings and stay.</p>
                                <hr class="border-light border-opacity-25 my-4">
                                <p class="small text-white-50 mb-0">Comfort, service, and hospitality—all in one place.</p>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="auth-card-form">
                                <p class="section-kicker">Guest Account</p>
                                <h2 class="h2 mb-2">Sign in to manage your stay</h2>
                                <p class="text-muted mb-4">Enter your account details to continue.</p>

                                @include('auth._login-form', ['loginAction' => route('login'), 'loginButton' => 'Sign In'])
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
