<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPaymentSlip;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingPaymentSlipService
{
    public function __construct(private MediaStorage $media) {}
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
        $path = $this->media->storeSlip($file, 'booking-'.$booking->id.'-'.\Illuminate\Support\Str::ulid().'.'.$file->extension());
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
            $this->media->deleteSlip($path);
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
        if (BookingPaymentSlip::where('file_path', $path)->exists()) {
            return;
        }
        if ($this->media->isCloudinary($path) && preg_match('~^cloudinary:utopia-bay/payment-slips/[A-Za-z0-9-]+\.(?:jpg|jpeg|png)$~i', $path)) {
            $this->media->deleteSlip($path);
        } elseif ($disk = self::diskFor($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
