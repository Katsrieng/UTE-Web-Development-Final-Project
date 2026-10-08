<?php

namespace App\Http\Controllers;

use App\Models\PaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentSettingsController extends Controller
{
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
                $path = $request->file('khqr_image')->store('payment-settings', 'public');
                if (! $path) {
                    throw new \RuntimeException('Unable to store the QR image. Please try again.');
                }
                $data['khqr_image'] = $path;
            }
            DB::transaction(function () use ($data, $path) {
                PaymentSetting::firstOrCreate(['id' => 1], ['account_name' => 'Beach Resort Management']);
                $settings = PaymentSetting::lockForUpdate()->findOrFail(1);
                $oldPath = $settings->khqr_image;
                $settings->update($data);
                if ($path && $oldPath && str_starts_with($oldPath, 'payment-settings/')) {
                    DB::afterCommit(fn () => Storage::disk('public')->delete($oldPath));
                }
            });
        } catch (\Throwable $exception) {
            if ($path) { Storage::disk('public')->delete($path); }
            throw $exception;
        }

        return redirect()->route('payment-settings.edit')->with('success', 'Payment settings updated.');
    }

    public function removeQr()
    {
        DB::transaction(function () {
            $settings = PaymentSetting::lockForUpdate()->find(1);
            if (! $settings || ! $settings->khqr_image) { return; }
            $oldPath = $settings->khqr_image;
            $settings->update(['khqr_image' => null]);
            if (str_starts_with($oldPath, 'payment-settings/')) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($oldPath));
            }
        });

        return redirect()->route('payment-settings.edit')->with('success', 'Custom QR removed. The default demo QR is now used.');
    }
}
