@extends('layouts.management')
@section('title', 'Notifications')
@section('page-label', 'Notifications')

@section('content')
<div class="page-heading staff-notifications-heading">
    <div>
        <p class="section-kicker">Booking Operations</p>
        <h1>Notifications</h1>
        <p>Booking activity that needs your attention.</p>
    </div>
    @if($unreadCount)
        <form method="POST" action="{{ route('staff.notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-check2-all" aria-hidden="true"></i> Mark all as read</button>
        </form>
    @endif
</div>
<div class="staff-notifications-panel">
    <nav class="staff-notification-tabs" aria-label="Notification filters">
        <a href="{{ route('staff.notifications.index') }}" class="{{ $filter === 'all' ? 'active' : '' }}" @if($filter === 'all') aria-current="page" @endif>All</a>
        <a href="{{ route('staff.notifications.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'active' : '' }}" @if($filter === 'unread') aria-current="page" @endif>Unread <span>{{ $unreadCount }}</span></a>
    </nav>
    @forelse($notifications as $notification)
        @include('partials.staff-notification-item', ['compact' => false])
    @empty
        <div class="staff-notification-empty staff-notification-empty-page">
            <i class="bi bi-bell" aria-hidden="true"></i>
            <strong>{{ $filter === 'unread' ? 'No unread notifications' : "You're all caught up" }}</strong>
            <span>{{ $filter === 'unread' ? 'Every booking notification has been read.' : 'New booking notifications will appear here.' }}</span>
        </div>
    @endforelse
</div>
<div class="mt-3">{{ $notifications->links('pagination::bootstrap-5') }}</div>
@endsection
