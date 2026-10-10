@php
    $customerNotifications = auth()->user()->notifications()->whereIn('type', \App\Support\CustomerNotificationTypes::ALL);
    $customerUnreadCount = (clone $customerNotifications)->whereNull('read_at')->count();
    $recentCustomerNotifications = $customerNotifications->latest()->limit(5)->get();
@endphp
<div class="dropdown staff-notification-dropdown customer-notification-dropdown">
    <button class="btn staff-notification-bell" type="button" data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="Notifications, {{ $customerUnreadCount }} unread" title="Notifications">
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if($customerUnreadCount)
            <span class="staff-notification-count" aria-hidden="true">{{ $customerUnreadCount > 9 ? '9+' : $customerUnreadCount }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end staff-notification-menu">
        <div class="staff-notification-menu-header">
            <strong>Notifications</strong>
            @if($customerUnreadCount)
                <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="staff-notification-text-action">Mark all as read</button>
                </form>
            @endif
        </div>
        @forelse($recentCustomerNotifications as $notification)
            @include('partials.customer-notification-item', ['compact' => true])
        @empty
            <div class="staff-notification-empty">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <strong>You're all caught up</strong>
                <span>Booking, payment, membership and event updates will appear here.</span>
            </div>
        @endforelse
        <a class="staff-notification-view-all" href="{{ route('customer.notifications.index') }}">
            View all notifications <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    </div>
</div>
