@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="auth-card">
                    <div class="row g-0">
                        <div class="col-lg-4 d-none d-lg-block">
                            <div class="auth-card-aside">
                                <i class="bi bi-stars"></i>
                                <h1 class="h2 mt-4">Your stay starts here</h1>
                                <p class="text-white-50 lh-lg">Create a customer account to request event venues, manage reservations, and access membership benefits.</p>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="auth-card-form">
                                <p class="section-kicker">Join us</p>
                                <h2 class="h2 mb-2">Create a customer account</h2>
                                <p class="text-muted mb-4">A few details are all you need to get started.</p>

                                @if($errors->any())
                                    <div class="validation-summary mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please check your information.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
                                @endif

                                <form method="POST" action="{{ route('register') }}">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="name" class="form-label">Full name</label>
                                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required autofocus autocomplete="name">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Phone <span class="text-muted fw-normal">(optional)</span></label>
                                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror" autocomplete="tel">
                                        </div>
                                        <div class="col-12">
                                            <label for="email" class="form-label">Email address</label>
                                            <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autocomplete="email">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password" class="form-label">Password</label>
                                            <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="new-password">
                                            <div class="form-text">At least 8 characters, including letters and numbers.</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="password_confirmation" class="form-label">Confirm password</label>
                                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required autocomplete="new-password">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-hotel w-100 mt-4">Create account</button>
                                </form>
                                <p class="text-center text-muted mt-4 mb-0">Already registered? <a href="{{ route('login') }}" class="fw-semibold">Sign in</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
