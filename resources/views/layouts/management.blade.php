<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Management') | Hotel & Hospitality</title>

    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/hotel.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="management-body">
    <div class="management-shell">
        @include('partials.management-sidebar')

        <div class="management-content">
            <header class="management-topbar">
                <button class="btn sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#managementSidebar" aria-controls="managementSidebar" aria-label="Open navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div>
                    <p class="management-eyebrow mb-0">Hotel operations</p>
                    <strong>@yield('page-label', 'Management')</strong>
                </div>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <a href="{{ route('welcome') }}" class="topbar-link d-none d-sm-inline-flex">
                        <i class="bi bi-box-arrow-up-right"></i> Public site
                    </a>
                    <div class="dropdown">
                        <button class="btn user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar-circle">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="management-main">
                @include('partials.flash-messages')
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}" defer></script>
    <script src="{{ asset('js/hotel.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
