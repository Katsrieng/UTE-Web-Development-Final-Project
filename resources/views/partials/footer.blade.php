<footer class="site-footer">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <a class="footer-brand" href="{{ route('welcome') }}">
                    <span class="brand-mark"><i class="bi bi-buildings"></i></span>
                    <span><strong>Hotel &amp; Hospitality</strong><small>Management System</small></span>
                </a>
                <p class="footer-copy">Comfortable stays, thoughtful events, and dependable hospitality managed in one place.</p>
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
                    <a href="{{ route(auth()->user()->isCustomer() ? 'customer.bookings.index' : 'dashboard') }}">{{ auth()->user()->isCustomer() ? 'My Bookings' : 'Management Dashboard' }}</a>
                @else
                    <a href="{{ route('login') }}">Sign in</a>
                    <a href="{{ route('register') }}">Create an account</a>
                @endauth
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ now()->year }} Hotel &amp; Hospitality Management System</span>
            <span>Design inspired by <a href="https://colorlib.com/wp/template/sona/" target="_blank" rel="noopener">Sona by Colorlib</a>, distributed by ThemeWagon.</span>
            <a href="{{ route('staff.login') }}" class="footer-staff-access"><i class="bi bi-buildings" aria-hidden="true"></i><span>Staff Access</span><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
    </div>
</footer>
