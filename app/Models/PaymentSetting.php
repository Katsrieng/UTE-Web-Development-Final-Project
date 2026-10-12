<?php

namespace App\Models;

use App\Services\MediaStorage;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    public const BUNDLED_QR_ACCOUNT_NAME = 'RAVANMONYVAN HAY';

    protected $fillable = ['account_name', 'account_label', 'khqr_image', 'aba_khqr_enabled'];

    protected function casts(): array
    {
        return ['aba_khqr_enabled' => 'boolean'];
    }

    public static function current(): self
    {
        return static::find(1) ?? new static(['account_name' => self::BUNDLED_QR_ACCOUNT_NAME, 'aba_khqr_enabled' => false]);
    }

    public function khqrAvailable(): bool
    {
        return $this->aba_khqr_enabled && $this->qrUrl() !== null;
    }

    public function hasCustomQr(): bool
    {
        return $this->khqr_image && app(MediaStorage::class)->publicExists($this->khqr_image);
    }

    public function qrAccountName(): string
    {
        return $this->hasCustomQr() ? $this->account_name : self::BUNDLED_QR_ACCOUNT_NAME;
    }

    public function qrUrl(): ?string
    {
        if ($this->hasCustomQr()) {
            return app(MediaStorage::class)->publicUrl($this->khqr_image);
        }

        return is_file(public_path('images/qr-khqr.png')) ? asset('images/qr-khqr.png') : null;
    }
}
