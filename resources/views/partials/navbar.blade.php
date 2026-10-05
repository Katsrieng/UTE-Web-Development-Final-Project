<header class="site-header">
    <div class="header-top d-none d-lg-block">
        <div class="container d-flex align-items-center justify-content-between">
            <div class="d-flex gap-4">
                <span><i class="bi bi-geo-alt me-2"></i>Phnom Penh, Cambodia</span>
                <span><i class="bi bi-clock me-2"></i>Hospitality, every day</span>
            </div>
            <div>Hotel &amp; Hospitality Management System</div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg sona-navbar" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand hotel-brand" href="{{ route('welcome') }}">
                <span class="brand-mark"><i class="bi bi-buildings"></i></span>
                <span><strong>Hotel</strong><small>&amp; Hospitality</small></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation"
                    aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavigation">
                <ul class="navbar-nav mx-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('welcome') ? 'active' : '' }}" href="{{ route('welcome') }}">Home</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('rooms.*') || request()->routeIs('room-types.*') ? 'active' : '' }}" href="{{ route('rooms.index') }}">Rooms</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('facilities.*') ? 'active' : '' }}" href="{{ route('facilities.index') }}">Facilities</a></li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('venues.*') ? 'active' : '' }}" href="{{ route('venues.index') }}">Events &amp; Venues</a></li>

                    @auth
                        @if(auth()->user()->isCustomer())
                            @if(Route::has('memberships.index'))
                                <li class="nav-item"><a class="nav-link {{ request()->routeIs('memberships.*') ? 'active' : '' }}" href="{{ route('memberships.index') }}">Membership</a></li>
                            @endif
                            <li class="nav-item"><a class="nav-link {{ request()->routeIs('event-reservations.*') ? 'active' : '' }}" href="{{ route('event-reservations.index') }}">My Events</a></li>
                        @endif
                        @if(auth()->user()->hasRole('admin', 'staff'))
                            <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">Management</a></li>
                        @endif
                    @endauth
                </ul>

                <div class="navbar-actions d-flex flex-column flex-lg-row align-items-lg-center gap-2 mt-3 mt-lg-0">
                    @auth
                        <a class="profile-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
                            <span class="avatar-circle">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="d-lg-none d-xl-inline">{{ auth()->user()->name }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-hotel-outline btn-sm w-100">Logout</button>
                        </form>
                    @else
                        <a class="nav-auth-link" href="{{ route('login') }}">Sign in</a>
                        <a class="btn btn-hotel btn-sm" href="{{ route('register') }}">Create account</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>
