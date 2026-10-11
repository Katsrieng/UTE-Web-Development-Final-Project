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
        $paymentTotals = Payment::selectRaw(<<<'SQL'
            COUNT(*) AS total_payments,
            SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) AS paid_payments,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_payments,
            SUM(CASE WHEN status = 'Refunded' THEN 1 ELSE 0 END) AS refunded_payments,
            COALESCE(SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END), 0) AS total_revenue,
            COALESCE(SUM(CASE WHEN status = 'Paid' AND DATE(payment_date) = ? THEN amount ELSE 0 END), 0) AS today_revenue,
            COALESCE(SUM(CASE WHEN status = 'Paid' AND booking_id IS NOT NULL THEN amount ELSE 0 END), 0) AS room_booking_revenue,
            COALESCE(SUM(CASE WHEN status = 'Paid' AND event_booking_id IS NOT NULL THEN amount ELSE 0 END), 0) AS event_booking_revenue
            SQL, [$today])->first();
        $totalPayments = (int) $paymentTotals->total_payments;
        $paidPayments = (int) $paymentTotals->paid_payments;
        $pendingPayments = (int) $paymentTotals->pending_payments;
        $refundedPayments = (int) $paymentTotals->refunded_payments;
        $totalRevenue = (float) $paymentTotals->total_revenue;
        $todayRevenue = (float) $paymentTotals->today_revenue;
        $roomBookingRevenue = (float) $paymentTotals->room_booking_revenue;
        $eventBookingRevenue = (float) $paymentTotals->event_booking_revenue;
        $pendingEvents = EventBooking::where('status', EventBooking::STATUS_PENDING)->count();
        $relations = ['user', 'room', 'payments'];
        $arrivals = Booking::with($relations)->where('status', 'Confirmed')->whereDate('check_in_date', $today)->orderBy('id')->limit(5)->get();
        $departures = Booking::with($relations)->where('status', 'Checked In')->whereDate('check_out_date', $today)->orderBy('id')->limit(5)->get();
        $recentBookings = Booking::with($relations)->latest()->orderByDesc('id')->limit(5)->get();
        $paymentAttention = Payment::with('user')->where('status', 'Pending')->oldest()->orderBy('id')->limit(5)->get();

        $recentPayments = Payment::with('user')
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
