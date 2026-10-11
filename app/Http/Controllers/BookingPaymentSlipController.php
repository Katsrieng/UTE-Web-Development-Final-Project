<?php

namespace App\Http\Controllers;

use App\Models\BookingPaymentSlip;
use App\Services\BookingPaymentSlipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingPaymentSlipController extends Controller
{
    public function show(Request $request, BookingPaymentSlip $paymentSlip): StreamedResponse
    {
        $user = $request->user();
        $booking = $paymentSlip->booking;
        abort_unless($booking && (
            ($user->isCustomer() && $booking->user_id === $user->id)
            || ($user->hasRole('admin', 'manager', 'staff')
                && ($user->hasPermission('view_bookings') || $user->hasPermission('view_payments')))
        ), 403);

        $path = $paymentSlip->file_path;
        $disk = BookingPaymentSlipService::diskFor($path);
        abort_unless($disk && Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, 'payment-slip-'.$paymentSlip->id, [
            'Content-Type' => $paymentSlip->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
