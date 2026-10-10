<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\NewBookingNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $query = $request->user()->notifications()->where('type', NewBookingNotification::class);
        $unreadCount = (clone $query)->whereNull('read_at')->count();
        $notifications = $query->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()->paginate(12)->withQueryString();

        return view('management.notifications.index', compact('notifications', 'unreadCount', 'filter'));
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->ownedNotification($request, $notification);
        $notification->markAsRead();
        $booking = Booking::find($notification->data['booking_id'] ?? null);

        return $booking
            ? redirect()->route('bookings.show', $booking)
            : redirect()->route('staff.notifications.index')->with('warning', 'This booking is no longer available.');
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $this->ownedNotification($request, $notification)->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->where('type', NewBookingNotification::class)->update(['read_at' => now()]);

        return back();
    }

    private function ownedNotification(Request $request, string $id): \Illuminate\Notifications\DatabaseNotification
    {
        return $request->user()->notifications()->where('type', NewBookingNotification::class)
            ->whereKey($id)->firstOrFail();
    }
}
