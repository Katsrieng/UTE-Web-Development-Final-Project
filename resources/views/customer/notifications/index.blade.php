@extends('layouts.app')
@section('title', 'My Notifications')

@section('content')
<div class="container py-4 py-lg-5 customer-notifications-page">
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
        <div>
            <p class="section-kicker mb-1">Your stay at Utopia Bay</p>
            <h1 class="h2 mb-1">Notifications</h1>
            <p class="text-muted mb-0">Updates about your bookings, payments, events and membership.</p>
        </div>
        @if($unreadCount)
            <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                @csrf
                <button class="btn btn-hotel-outline btn-sm" type="submit"><i class="bi bi-check2-all" aria-hidden="true"></i> Mark all as read</button>
            </form>
        @endif
    </div>
    <div class="staff-notifications-panel">
        <nav class="staff-notification-tabs" aria-label="Notification filters">
            <a href="{{ route('customer.notifications.index') }}" class="{{ $filter === 'all' ? 'active' : '' }}" @if($filter === 'all') aria-current="page" @endif>All</a>
            <a href="{{ route('customer.notifications.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'active' : '' }}" @if($filter === 'unread') aria-current="page" @endif>Unread <span>{{ $unreadCount }}</span></a>
        </nav>
        @forelse($notifications as $notification)
            @include('partials.customer-notification-item', ['compact' => false])
        @empty
            <div class="staff-notification-empty staff-notification-empty-page">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <strong>{{ $filter === 'unread' ? 'No unread notifications' : "You're all caught up" }}</strong>
                <span>{{ $filter === 'unread' ? 'All of your updates have been read.' : 'Booking, payment, membership and event updates will appear here.' }}</span>
            </div>
        @endforelse
    </div>
    <div class="mt-3">{{ $notifications->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
