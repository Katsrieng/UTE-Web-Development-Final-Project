<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPaymentSlip;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingPaymentSlipService
{
    public static function rules(): array
    {
        return ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'];
    }

    public function replace(Booking $booking, UploadedFile $file): void
    {
        $existing = $booking->paymentSlip()->first();
        $path = $file->storeAs('payment-slips', 'booking-'.$booking->id.'-'.\Illuminate\Support\Str::ulid().'.'.$file->extension(), 'public');
        if (! $path) {
            throw new \RuntimeException('Unable to store the payment slip. Please try again.');
        }
        try {
            $booking->paymentSlip()->updateOrCreate(['booking_id' => $booking->id], [
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'reviewed' => false,
            ]);
            if ($existing) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($existing->file_path));
            }
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function delete(BookingPaymentSlip $slip): void
    {
        $path = $slip->file_path;
        $slip->delete();
        DB::afterCommit(fn () => Storage::disk('public')->delete($path));
    }
}
