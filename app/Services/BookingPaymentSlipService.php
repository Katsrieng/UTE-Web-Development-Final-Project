<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPaymentSlip;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingPaymentSlipService
{
    public const PRIVATE_DIRECTORY = 'booking-payment-slips';

    public static function diskFor(string $path): ?string
    {
        if (preg_match('~^'.self::PRIVATE_DIRECTORY.'/booking-[0-9]+-[A-Za-z0-9]+\.(?:jpg|jpeg|png)$~i', $path)) {
            return 'local';
        }

        // Existing demo uploads used the public disk. Move them with payment-slips:privatize before hosting.
        return preg_match('~^payment-slips/booking-[0-9]+-[A-Za-z0-9]+\.(?:jpg|jpeg|png)$~i', $path)
            ? 'public' : null;
    }

    public static function rules(): array
    {
        return ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'];
    }

    public function replace(Booking $booking, UploadedFile $file): void
    {
        $existing = $booking->paymentSlip()->first();
        $path = $file->storeAs(self::PRIVATE_DIRECTORY, 'booking-'.$booking->id.'-'.\Illuminate\Support\Str::ulid().'.'.$file->extension(), 'local');
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
                DB::afterCommit(fn () => $this->cleanup($existing->file_path));
            }
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function delete(BookingPaymentSlip $slip): void
    {
        $path = $slip->file_path;
        $slip->delete();
        DB::afterCommit(fn () => $this->cleanup($path));
    }

    private function cleanup(string $path): void
    {
        $disk = self::diskFor($path);
        if ($disk && ! BookingPaymentSlip::where('file_path', $path)->exists()) {
            Storage::disk($disk)->delete($path);
        }
    }
}
