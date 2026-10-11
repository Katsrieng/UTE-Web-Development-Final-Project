<?php

namespace App\Http\Controllers;

use App\Models\BookingPaymentSlip;
use App\Services\BookingPaymentSlipService;
use App\Services\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;

class BookingPaymentSlipController extends Controller
{
    public function show(Request $request, BookingPaymentSlip $paymentSlip, MediaStorage $media): StreamedResponse|RedirectResponse
    {
        $user = $request->user();
        $booking = $paymentSlip->booking;
        abort_unless($booking && (
            ($user->isCustomer() && $booking->user_id === $user->id)
            || ($user->hasRole('admin', 'manager', 'staff')
                && ($user->hasPermission('view_bookings') || $user->hasPermission('view_payments')))
        ), 403);

        $path = $paymentSlip->file_path;
        if ($media->isCloudinary($path)) {
            abort_unless($media->slipExists($path), 404);

            return redirect()->away($media->slipDownloadUrl($path));
        }
        $disk = BookingPaymentSlipService::diskFor($path);
        abort_unless($disk && Storage::disk($disk)->exists($path), 404);

        return Storage::disk($disk)->response($path, 'payment-slip-'.$paymentSlip->id, [
            'Content-Type' => $paymentSlip->mime_type,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
