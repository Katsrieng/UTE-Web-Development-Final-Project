<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Booking;
use App\Models\Room;
use App\Models\EventBooking;

class DashboardController extends Controller
{
    public function index()
    {
        $today = today()->toDateString();
        $counts = Booking::selectRaw("SUM(CASE WHEN DATE(check_in_date) = ? AND status = 'Confirmed' THEN 1 ELSE 0 END) AS arrivals, SUM(CASE WHEN DATE(check_out_date) = ? AND status = 'Checked In' THEN 1 ELSE 0 END) AS departures, SUM(CASE WHEN status = 'Checked In' THEN 1 ELSE 0 END) AS checked_in, SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) AS confirmed", [$today, $today])->first();
        $operations = collect($counts->getAttributes())->map(fn ($value) => (int) $value)->all();
        $roomCounts = Room::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->map(fn ($value) => (int) $value)->all();
        $todayRevenue = Payment::where('status', 'Paid')->whereDate('payment_date', $today)->sum('amount');
        $pendingEvents = EventBooking::where('status', EventBooking::STATUS_PENDING)->count();
        $relations = ['user', 'room.roomType', 'payments', 'paymentSlip'];
        $arrivals = Booking::with($relations)->where('status', 'Confirmed')->whereDate('check_in_date', $today)->orderBy('id')->limit(5)->get();
        $departures = Booking::with($relations)->where('status', 'Checked In')->whereDate('check_out_date', $today)->orderBy('id')->limit(5)->get();
        $recentBookings = Booking::with($relations)->latest()->orderByDesc('id')->limit(5)->get();
        $paymentAttention = Payment::with('user')->where('status', 'Pending')->oldest()->orderBy('id')->limit(5)->get();

        $totalRevenue = Payment::where('status', 'Paid')->sum('amount');

        $totalPayments = Payment::count();

        $paidPayments = Payment::where('status', 'Paid')->count();

        $pendingPayments = Payment::where('status', 'Pending')->count();

        $refundedPayments = Payment::where('status', 'Refunded')->count();

        $roomBookingRevenue = Payment::where('status', 'Paid')
            ->whereNotNull('booking_id')
            ->sum('amount');

        $eventBookingRevenue = Payment::where('status', 'Paid')
            ->whereNotNull('event_booking_id')
            ->sum('amount');

        $recentPayments = Payment::with(['user', 'booking.room', 'eventBooking.venue'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalPayments',
            'paidPayments',
            'pendingPayments',
            'refundedPayments',
            'roomBookingRevenue',
            'eventBookingRevenue',
            'recentPayments', 'operations', 'roomCounts', 'todayRevenue', 'pendingEvents',
            'arrivals', 'departures', 'recentBookings', 'paymentAttention'
        ));
    }
}
