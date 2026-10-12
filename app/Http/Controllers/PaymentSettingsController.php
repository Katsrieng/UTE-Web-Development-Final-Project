<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use App\Services\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentSettingsController extends Controller
{
    public function __construct(private MediaStorage $media) {}

    public function edit()
    {
        return view('payments.settings', ['settings' => PaymentSetting::current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'account_name' => ['required', 'string', 'max:191'],
            'account_label' => ['nullable', 'string', 'max:191'],
            'aba_khqr_enabled' => ['required', 'boolean'],
            'khqr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        unset($data['khqr_image']);
        $path = null;
        try {
            if ($request->hasFile('khqr_image')) {
                $path = $this->media->storePublic($request->file('khqr_image'), 'payment-settings');
                if (! $path) {
                    throw new \RuntimeException('Unable to store the QR image. Please try again.');
                }
                $data['khqr_image'] = $path;
            }
            DB::transaction(function () use ($data, $path) {
                PaymentSetting::firstOrCreate(['id' => 1], ['account_name' => PaymentSetting::BUNDLED_QR_ACCOUNT_NAME]);
                $settings = PaymentSetting::lockForUpdate()->findOrFail(1);
                $oldPath = $settings->khqr_image;
                $settings->update($data);
                if ($path && $oldPath && $this->ownedQr($oldPath)) {
                    DB::afterCommit(fn () => $this->media->deletePublic($oldPath));
                }
            });
        } catch (\Throwable $exception) {
            if ($path) {
                $this->media->deletePublic($path);
            }
            throw $exception;
        }

        return redirect()->route('payment-settings.edit')->with('success', 'Payment settings updated.');
    }

    public function removeQr()
    {
        DB::transaction(function () {
            $settings = PaymentSetting::lockForUpdate()->find(1);
            if (! $settings || ! $settings->khqr_image) {
                return;
            }
            $oldPath = $settings->khqr_image;
            $settings->update(['khqr_image' => null]);
            if ($this->ownedQr($oldPath)) {
                DB::afterCommit(fn () => $this->media->deletePublic($oldPath));
            }
        });

        return redirect()->route('payment-settings.edit')->with('success', 'Custom QR removed. The bundled ABA QR is now used.');
    }

    private function ownedQr(string $path): bool
    {
        return (bool) preg_match('~^(?:payment-settings/[A-Za-z0-9]+\.(?:jpg|jpeg|png|webp)|cloudinary:utopia-bay/payment-settings/[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp))$~i', $path);
    }
}
