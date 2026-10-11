<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use App\Models\BookingPaymentSlip;
use App\Services\BookingPaymentSlipService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payment-slips:privatize', function () {
    $moved = 0;
    $missing = 0;
    foreach (BookingPaymentSlip::query()->orderBy('id')->cursor() as $slip) {
        $old = $slip->file_path;
        if (BookingPaymentSlipService::diskFor($old) !== 'public') {
            continue;
        }
        if (! Storage::disk('public')->exists($old)) {
            $this->warn("Slip {$slip->id}: public file is missing; record left unchanged.");
            $missing++;
            continue;
        }
        $new = BookingPaymentSlipService::PRIVATE_DIRECTORY.'/'.basename($old);
        $contents = Storage::disk('public')->get($old);
        if (! Storage::disk('local')->put($new, $contents)) {
            $this->error("Slip {$slip->id}: private copy failed; public file left unchanged.");
            continue;
        }
        try {
            $slip->update(['file_path' => $new]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($new);
            throw $exception;
        }
        if (! BookingPaymentSlip::where('file_path', $old)->exists()) {
            if (! Storage::disk('public')->delete($old)) {
                $this->warn("Slip {$slip->id}: remove the old public copy before hosting.");
            }
        }
        $moved++;
    }
    $this->info("Moved {$moved} payment slip(s) to private storage; {$missing} missing file(s).");
})->purpose('Move legacy public booking payment slips to private storage before hosting');
