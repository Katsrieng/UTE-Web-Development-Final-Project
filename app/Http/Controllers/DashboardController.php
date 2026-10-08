<?php

namespace App\Http\Controllers;

use App\Models\Payment;

class DashboardController extends Controller
{
    public function index()
    {
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
            'recentPayments'
        ));
    }
}
