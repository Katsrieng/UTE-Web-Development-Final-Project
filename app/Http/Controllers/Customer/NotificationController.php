<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EventBooking;
use App\Models\MembershipPurchase;
use App\Models\Payment;
use App\Notifications\BookingStatusUpdatedNotification;
use App\Notifications\EventReservationStatusUpdatedNotification;
use App\Notifications\MembershipActivatedNotification;
use App\Notifications\PaymentStatusUpdatedNotification;
use App\Support\CustomerNotificationTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';
        $query = $request->user()->notifications()->whereIn('type', CustomerNotificationTypes::ALL);
        $unreadCount = (clone $query)->whereNull('read_at')->count();
        $notifications = $query->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()->paginate(12)->withQueryString();

        return view('customer.notifications.index', compact('notifications', 'unreadCount', 'filter'));
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $notification = $this->ownedNotification($request, $notification);
        $notification->markAsRead();
        $data = $notification->data;
        $userId = $request->user()->id;

        if ($notification->type === BookingStatusUpdatedNotification::class) {
            $booking = Booking::where('user_id', $userId)->find($data['booking_id'] ?? null);
            if ($booking) { return redirect()->route('customer.bookings.show', $booking); }
        } elseif ($notification->type === PaymentStatusUpdatedNotification::class) {
            $payment = Payment::where('user_id', $userId)->find($data['payment_id'] ?? null);
            if ($payment && (! $payment->booking_id || $payment->booking?->user_id === $userId)) {
                return redirect()->route('customer.payments.show', $payment);
            }
        } elseif ($notification->type === EventReservationStatusUpdatedNotification::class) {
            $event = EventBooking::where('user_id', $userId)->find($data['event_booking_id'] ?? null);
            if ($event) { return redirect()->route('event-reservations.show', $event); }
        } elseif ($notification->type === MembershipActivatedNotification::class) {
            if (MembershipPurchase::where('user_id', $userId)->whereKey($data['membership_purchase_id'] ?? null)->exists()) {
                return redirect()->route('memberships.index');
            }
        }

        return redirect()->route('customer.notifications.index')->with('warning', 'This update is no longer available.');
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $this->ownedNotification($request, $notification)->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->whereIn('type', CustomerNotificationTypes::ALL)->update(['read_at' => now()]);

        return back();
    }

    private function ownedNotification(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereIn('type', CustomerNotificationTypes::ALL)
            ->whereKey($id)->firstOrFail();
    }
}
