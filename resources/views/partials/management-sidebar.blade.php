<aside class="management-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="managementSidebar" aria-label="Management navigation">
    <div class="offcanvas-header border-bottom border-light border-opacity-10">

@staffroute('dashboard')
<a class="management-brand" href="{{ route('dashboard') }}">
            <span class="brand-mark"><i class="bi bi-buildings"></i></span>
            <span><strong>Hotel</strong><small>Management</small></span>
        </a>
@endstaffroute

        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#managementSidebar" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body d-flex flex-column p-0">
        <nav class="management-nav flex-grow-1">
            <p class="nav-section-label">Overview</p>
            <a class="management-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2"></i><span>Dashboard</span></a>

            <p class="nav-section-label">Hotel operations</p>
            <a class="management-nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}" href="{{ route('bookings.index') }}"><i class="bi bi-calendar-check"></i><span>Bookings</span></a>
            <a class="management-nav-link {{ request()->routeIs('management.rooms.*') ? 'active' : '' }}" href="{{ route('management.rooms.index') }}"><i class="bi bi-door-open"></i><span>Rooms</span></a>
            <a class="management-nav-link {{ request()->routeIs('management.room-types.*') ? 'active' : '' }}" href="{{ route('management.room-types.index') }}"><i class="bi bi-house-door"></i><span>Room Types</span></a>
            <a class="management-nav-link {{ request()->routeIs('management.facilities.*') ? 'active' : '' }}" href="{{ route('management.facilities.index') }}"><i class="bi bi-stars"></i><span>Facilities</span></a>
            <a class="management-nav-link {{ request()->routeIs('management.venues.*') ? 'active' : '' }}" href="{{ route('management.venues.index') }}"><i class="bi bi-building"></i><span>Venues</span></a>
            <a class="management-nav-link {{ request()->routeIs('management.event-reservations.*') ? 'active' : '' }}" href="{{ route('management.event-reservations.index') }}"><i class="bi bi-calendar2-check"></i><span>Event Reservations</span></a>
            <a class="management-nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="bi bi-credit-card"></i><span>Payments</span></a>
            <a class="management-nav-link {{ request()->routeIs('payment-settings.*') ? 'active' : '' }}" href="{{ route('payment-settings.edit') }}"><i class="bi bi-qr-code"></i><span>Payment Settings</span></a>

            @if(auth()->user()->hasPermission('manage_users') || auth()->user()->hasPermission('manage_roles_permissions'))
                <p class="nav-section-label">Administration</p>
            @endif
            @staffroute('admin.users.index')
            <a class="management-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-person-badge"></i><span>Staff Accounts</span></a>
            @endstaffroute
            @staffroute('admin.roles.index')
            <a class="management-nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}"><i class="bi bi-shield-lock"></i><span>Roles &amp; Permissions</span></a>
            @endstaffroute
            @if(auth()->user()->hasPermission('view_customers'))
                <p class="nav-section-label">Customer Management</p>
            @endif
            @staffroute('management.customers.index')
            <a class="management-nav-link {{ request()->routeIs('management.customers.*') ? 'active' : '' }}" href="{{ route('management.customers.index') }}"><i class="bi bi-people"></i><span>Customers</span></a>
            @endstaffroute
            @if(auth()->user()->hasPermission('view_memberships') || auth()->user()->hasPermission('manage_packages'))
                <p class="nav-section-label">Programs</p>
            @endif
            @staffroute('admin.membership-types.index')
            <a class="management-nav-link {{ request()->routeIs('admin.membership-types.*') ? 'active' : '' }}" href="{{ route('admin.membership-types.index') }}"><i class="bi bi-award"></i><span>Membership Types</span></a>
            @endstaffroute
            @staffroute('admin.packages.index')
            <a class="management-nav-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}" href="{{ route('admin.packages.index') }}"><i class="bi bi-gift"></i><span>Packages</span></a>
            @endstaffroute

        </nav>

        <div class="sidebar-footer">
            <span class="avatar-circle">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <div class="overflow-hidden">
                <strong class="d-block text-truncate">{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->roleLabel() }}</small>
            </div>
        </div>
    </div>
</aside>
