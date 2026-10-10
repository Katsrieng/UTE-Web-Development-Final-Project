@php
    $details = $notification->data;
    $unread = $notification->read_at === null;
@endphp
<div class="staff-notification-item {{ $unread ? 'is-unread' : '' }}">
    <form method="POST" action="{{ route('customer.notifications.open', $notification->id) }}" class="staff-notification-open-form">
        @csrf
        <button type="submit" class="staff-notification-open">
            <span class="staff-notification-icon"><i class="bi {{ match($notification->type) {
                \App\Notifications\BookingStatusUpdatedNotification::class => 'bi-calendar-check',
                \App\Notifications\PaymentStatusUpdatedNotification::class => 'bi-credit-card',
                \App\Notifications\EventReservationStatusUpdatedNotification::class => 'bi-calendar-event',
                default => 'bi-award',
            } }}" aria-hidden="true"></i></span>
            <span class="staff-notification-copy">
                <span class="staff-notification-title">{{ $details['title'] ?? 'Account update' }} @if($unread)<span class="visually-hidden">Unread.</span>@endif</span>
                <span class="staff-notification-context">{{ $details['context'] ?? $details['message'] ?? '' }}</span>
                <span class="staff-notification-meta">{{ $notification->created_at->diffForHumans() }}</span>
            </span>
            @if($unread)<span class="staff-notification-dot" aria-hidden="true"></span>@endif
        </button>
    </form>
    @if(empty($compact) && $unread)
        <form method="POST" action="{{ route('customer.notifications.read', $notification->id) }}" class="staff-notification-read-form">
            @csrf
            <button type="submit" class="staff-notification-text-action" aria-label="Mark {{ $details['title'] ?? 'notification' }} as read">
                <i class="bi bi-check-circle" aria-hidden="true"></i> Mark as read
            </button>
        </form>
    @endif
</div>
