@php
    $bookingNotifications = auth()->user()->notifications()->where('type', \App\Notifications\NewBookingNotification::class);
    $notificationUnreadCount = (clone $bookingNotifications)->whereNull('read_at')->count();
    $recentBookingNotifications = $bookingNotifications->latest()->limit(5)->get();
@endphp
<div class="dropdown staff-notification-dropdown">
    <button class="btn staff-notification-bell" type="button" data-bs-toggle="dropdown" aria-expanded="false"
            aria-label="Notifications, {{ $notificationUnreadCount }} unread" title="Notifications">
        <i class="bi bi-bell" aria-hidden="true"></i>
        @if($notificationUnreadCount)
            <span class="staff-notification-count" aria-hidden="true">{{ $notificationUnreadCount > 9 ? '9+' : $notificationUnreadCount }}</span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end staff-notification-menu">
        <div class="staff-notification-menu-header">
            <strong>Notifications</strong>
            @if($notificationUnreadCount)
                <form method="POST" action="{{ route('staff.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="staff-notification-text-action">Mark all as read</button>
                </form>
            @endif
        </div>
        @forelse($recentBookingNotifications as $notification)
            @include('partials.staff-notification-item', ['compact' => true])
        @empty
            <div class="staff-notification-empty">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <strong>You're all caught up</strong>
                <span>New booking notifications will appear here.</span>
            </div>
        @endforelse
        <a class="staff-notification-view-all" href="{{ route('staff.notifications.index') }}">
            View all notifications <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    </div>
</div>
