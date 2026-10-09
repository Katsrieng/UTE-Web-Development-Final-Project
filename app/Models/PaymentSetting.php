<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PaymentSetting extends Model
{
    protected $fillable = ['account_name', 'account_label', 'khqr_image', 'aba_khqr_enabled'];

    protected function casts(): array
    {
        return ['aba_khqr_enabled' => 'boolean'];
    }

    public static function current(): self
    {
        return static::find(1) ?? new static(['account_name' => 'Utopia Bay Resort', 'aba_khqr_enabled' => false]);
    }

    public function khqrAvailable(): bool
    {
        return $this->aba_khqr_enabled && $this->qrUrl() !== null;
    }

    public function hasCustomQr(): bool
    {
        return $this->khqr_image && Storage::disk('public')->exists($this->khqr_image);
    }

    public function qrUrl(): ?string
    {
        if ($this->hasCustomQr()) {
            return Storage::disk('public')->url($this->khqr_image);
        }

        return is_file(public_path('images/qr-khqr.png')) ? asset('images/qr-khqr.png') : null;
    }
}
