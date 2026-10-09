<footer class="site-footer">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <a class="footer-brand" href="{{ route('welcome') }}">
                    <img class="brand-logo" src="{{ asset('images/brand/utopia_bay_logo_wb.svg') }}" alt="" width="42" height="42">
                    <span><strong>Utopia Bay</strong><small>Resort</small></span>
                </a>
                <p class="footer-copy">Resort stays, experiences and hospitality in one place.</p>
            </div>
            <div class="col-6 col-lg-3">
                <h2 class="footer-title">Explore</h2>
                <a href="{{ route('rooms.index') }}">Rooms</a>
                <a href="{{ route('facilities.index') }}">Facilities</a>
                <a href="{{ route('venues.index') }}">Event Venues</a>
            </div>
            <div class="col-6 col-lg-4">
                <h2 class="footer-title">Your account</h2>
                @auth
                    <a href="{{ route('profile.edit') }}">My profile</a>
                    @if(auth()->user()->isCustomer())
                        @if(auth()->user()->is_active)
                            <a href="{{ route('customer.bookings.index') }}">My Bookings</a>
                        @endif
                    @else
                        @staffroute('dashboard')
                        <a href="{{ route('dashboard') }}">Management Dashboard</a>
                        @endstaffroute
                    @endif
                @else
                    <a href="{{ route('login') }}">Sign in</a>
                    <a href="{{ route('register') }}">Create an account</a>
                @endauth
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ now()->year }} Utopia Bay Resort Management System</span>
            <span>Design inspired by <a href="https://colorlib.com/wp/template/sona/" target="_blank" rel="noopener">Sona by Colorlib</a>, distributed by ThemeWagon.</span>
            <a href="{{ route('staff.login') }}" class="footer-staff-access"><i class="bi bi-buildings" aria-hidden="true"></i><span>Staff Access</span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
    </div>
</footer>
