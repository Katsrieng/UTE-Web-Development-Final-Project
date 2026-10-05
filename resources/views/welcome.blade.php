@extends('layouts.app')

@section('title', 'Hotel & Hospitality — Stay, Meet, Celebrate')

@section('content')
<section class="home-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="hero-content">
                    <p class="section-kicker text-warning">Welcome to better hospitality</p>
                    <h1>Stay beautifully. Meet effortlessly.</h1>
                    <p>Discover comfortable rooms, thoughtful facilities, and distinctive venues for weddings, meetings, and celebrations—all managed in one welcoming place.</p>
                    <div class="hero-actions">
                        <a href="{{ route('rooms.index') }}" class="btn btn-hotel">Explore rooms</a>
                        <a href="{{ route('venues.index') }}" class="btn btn-outline-light">Plan an event</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="hero-quick-card">
                    <p class="section-kicker">Start exploring</p>
                    <h2 class="h3 mb-3">Your hotel, your occasion</h2>
                    <a href="{{ route('rooms.index') }}" class="hero-quick-link">
                        <i class="bi bi-door-open"></i>
                        <span><strong>Find a room</strong><small>Browse room types, rates, and facilities</small></span>
                        <i class="bi bi-arrow-right ms-auto fs-6"></i>
                    </a>
                    <a href="{{ route('venues.index') }}" class="hero-quick-link">
                        <i class="bi bi-calendar2-heart"></i>
                        <span><strong>Host an event</strong><small>Weddings, meetings, and private parties</small></span>
                        <i class="bi bi-arrow-right ms-auto fs-6"></i>
                    </a>
                    <a href="{{ route('facilities.index') }}" class="hero-quick-link">
                        <i class="bi bi-stars"></i>
                        <span><strong>Discover facilities</strong><small>Everything designed around your comfort</small></span>
                        <i class="bi bi-arrow-right ms-auto fs-6"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="content-section bg-white">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <p class="section-kicker">About our hotel</p>
                <h2 class="section-title">Hospitality made simple</h2>
            </div>
            <div class="col-lg-7">
                <p class="section-copy mb-0">From arrival to celebration, our system brings rooms, facilities, memberships, events, and service together. Guests can explore with confidence while hotel teams manage every detail from one dependable workspace.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="text-center mb-5">
            <p class="section-kicker">What we offer</p>
            <h2 class="section-title">Discover our services</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3"><div class="service-card bg-white"><i class="bi bi-house-heart"></i><h3>Comfortable stays</h3><p>Rooms for solo trips, families, and special getaways.</p><a href="{{ route('rooms.index') }}" class="stretched-link" aria-label="Browse rooms"></a></div></div>
            <div class="col-md-6 col-lg-3"><div class="service-card bg-white"><i class="bi bi-calendar-event"></i><h3>Memorable events</h3><p>Beautiful venues for weddings, meetings, and parties.</p><a href="{{ route('venues.index') }}" class="stretched-link" aria-label="Browse venues"></a></div></div>
            <div class="col-md-6 col-lg-3"><div class="service-card bg-white"><i class="bi bi-gem"></i><h3>Guest facilities</h3><p>Useful amenities designed to make every visit easier.</p><a href="{{ route('facilities.index') }}" class="stretched-link" aria-label="Browse facilities"></a></div></div>
            <div class="col-md-6 col-lg-3"><div class="service-card bg-white"><i class="bi bi-award"></i><h3>Member benefits</h3><p>Extra value and recognition for returning guests.</p>@if(Route::has('memberships.index'))<a href="{{ auth()->check() ? route('memberships.index') : route('login') }}" class="stretched-link" aria-label="View memberships"></a>@endif</div></div>
        </div>
    </div>
</section>

<section class="content-section bg-white">
    <div class="container text-center">
        <p class="section-kicker">Make it yours</p>
        <h2 class="section-title">Ready to plan your next stay or event?</h2>
        <p class="section-copy mx-auto mb-4">Create an account to manage reservations, membership benefits, and your hotel experience.</p>
        @guest
            <a href="{{ route('register') }}" class="btn btn-hotel me-2">Create account</a>
            <a href="{{ route('login') }}" class="btn btn-outline-dark">Sign in</a>
        @else
            <a href="{{ route('home') }}" class="btn btn-hotel">Open my dashboard</a>
        @endguest
    </div>
</section>
@endsection
