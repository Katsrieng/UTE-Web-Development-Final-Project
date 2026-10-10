@php
    $details = $notification->data;
    $unread = $notification->read_at === null;
    $stayDates = collect([$details['check_in'] ?? null, $details['check_out'] ?? null])
        ->filter()->map(fn ($date) => \Carbon\Carbon::parse($date)->format('M j'))->join(' – ');
@endphp
<div class="staff-notification-item {{ $unread ? 'is-unread' : '' }}">
    <form method="POST" action="{{ route('staff.notifications.open', $notification->id) }}" class="staff-notification-open-form">
        @csrf
        <button type="submit" class="staff-notification-open">
            <span class="staff-notification-icon"><i class="bi bi-calendar-check" aria-hidden="true"></i></span>
            <span class="staff-notification-copy">
                <span class="staff-notification-title">New booking received @if($unread)<span class="visually-hidden">Unread.</span>@endif</span>
                <span class="staff-notification-context">{{ $details['customer_name'] ?? 'Customer' }} · {{ $details['room'] ?? 'Room' }}</span>
                <span class="staff-notification-meta">{{ $stayDates }} @if($stayDates)·@endif {{ $notification->created_at->diffForHumans() }}</span>
            </span>
            @if($unread)<span class="staff-notification-dot" aria-hidden="true"></span>@endif
        </button>
    </form>
    @if(empty($compact) && $unread)
        <form method="POST" action="{{ route('staff.notifications.read', $notification->id) }}" class="staff-notification-read-form">
            @csrf
            <button type="submit" class="staff-notification-text-action" aria-label="Mark booking notification from {{ $details['customer_name'] ?? 'customer' }} as read">
                <i class="bi bi-check-circle" aria-hidden="true"></i> Mark as read
            </button>
        </form>
    @endif
</div>
