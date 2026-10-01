{{-- Top navigation bar. No JavaScript needed, so it also works on the older
     standalone pages (payments, dashboard) that only load Bootstrap CSS. --}}
<nav class="navbar navbar-expand navbar-dark bg-dark">
    <div class="container flex-wrap">

        <a class="navbar-brand" href="{{ url('/') }}">Hotel &amp; Hospitality</a>

        <ul class="navbar-nav me-auto">
            @auth
                @if(auth()->user()->hasRole('admin', 'staff'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                           href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}"
                           href="{{ route('payments.index') }}">Payments</a>
                    </li>
                @endif

                @if(auth()->user()->isAdmin())
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                           href="{{ route('admin.users.index') }}">Users</a>
                    </li>
                @endif
            @endauth
        </ul>

        <ul class="navbar-nav align-items-center gap-2">
            @auth
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                       href="{{ route('profile.edit') }}">
                        {{ auth()->user()->name }}
                        <span class="badge text-bg-secondary">{{ ucfirst(auth()->user()->role) }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-light btn-sm">Logout</button>
                    </form>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('login') }}">Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-outline-light btn-sm" href="{{ route('register') }}">Register</a>
                </li>
            @endauth
        </ul>

    </div>
</nav>
