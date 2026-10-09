@extends('layouts.app')
@section('title', 'Membership checkout')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">Almost there</p><h1>Join {{ $membershipType->name }}</h1><p>Review your membership and choose how you would like to pay.</p></div></section>
<section class="content-section"><div class="container">
    @if($errors->any())<div class="validation-summary mb-4"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please correct the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <div class="row g-4 justify-content-center">
        <div class="col-lg-5">
            <article class="hotel-card p-4 p-lg-5 h-100">
                <span class="metric-icon"><i class="bi bi-gem"></i></span>
                <p class="section-kicker">{{ $membershipType->duration_months }} months</p>
                <h2 class="h3">{{ $membershipType->name }}</h2>
                <p class="text-muted">{{ $membershipType->description ?: 'Extra value for returning hotel guests.' }}</p>
                <ul class="list-unstyled mb-4">
                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>{{ number_format($membershipType->discount_percentage, 0) }}% off eligible bookings</li>
                    <li class="mb-2"><i class="bi bi-calendar-check me-2"></i>Valid until {{ now()->addMonths($membershipType->duration_months)->format('F j, Y') }}</li>
                </ul>
                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                    <span class="h5 mb-0">Total</span>
                    <strong class="display-6 font-display">${{ number_format($membershipType->price, 2) }}</strong>
                </div>
            </article>
        </div>
        <div class="col-lg-5">
            <div class="hotel-card p-4 p-lg-5 h-100">
                <h2 class="h4 mb-3">Payment method</h2>
                <form action="{{ route('memberships.subscribe') }}" method="POST">
                    @csrf
                    <input type="hidden" name="membership_type_id" value="{{ $membershipType->id }}">
                    @foreach($paymentMethods as $method)
                        <div class="form-check border rounded p-3 ps-5 mb-2">
                            <input class="form-check-input" type="radio" name="payment_method" id="method-{{ \Illuminate\Support\Str::slug($method) }}" value="{{ $method }}" @checked(old('payment_method', $paymentMethods[0]) === $method) required>
                            <label class="form-check-label w-100" for="method-{{ \Illuminate\Support\Str::slug($method) }}">
                                <strong>{{ $method }}</strong>
                                <small class="d-block text-muted">{{ $method === 'Card' ? 'Credit or debit card' : 'Pay by bank transfer' }}</small>
                            </label>
                        </div>
                    @endforeach
                    <p class="text-muted small mt-3">This is a demo checkout. No real charge is made, and no card details are collected or stored.</p>
                    <button type="submit" class="btn btn-hotel w-100 mt-2">Pay ${{ number_format($membershipType->price, 2) }} &amp; join</button>
                    <a href="{{ route('memberships.index') }}" class="btn btn-outline-secondary w-100 mt-2">Back to memberships</a>
                </form>
            </div>
        </div>
    </div>
</div></section>
@endsection
