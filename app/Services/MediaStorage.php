<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaStorage
{
    private ?Cloudinary $cloudinary = null;

    public function storePublic(UploadedFile $file, string $folder): string
    {
        $driver = config('media.public_driver');
        if ($driver === 'local') {
            return $file->store($folder, 'public') ?: throw new RuntimeException('Unable to store public media.');
        }
        if ($driver !== 'cloudinary') {
            throw new RuntimeException('Unsupported public media driver.');
        }

        return $this->upload($file->getRealPath(), 'utopia-bay/'.$folder, false);
    }

    public function storeSlip(UploadedFile $file, string $localName): string
    {
        $driver = config('media.payment_slip_driver');
        if ($driver === 'local') {
            return $file->storeAs(BookingPaymentSlipService::PRIVATE_DIRECTORY, $localName, 'local')
                ?: throw new RuntimeException('Unable to store the payment slip.');
        }
        if ($driver !== 'cloudinary') {
            throw new RuntimeException('Unsupported payment slip driver.');
        }

        return $this->upload($file->getRealPath(), 'utopia-bay/payment-slips', true);
    }

    /** Upload an existing local file without changing the application's runtime storage driver. */
    public function migratePublicFile(string $absolutePath, string $folder): string
    {
        return $this->upload($absolutePath, 'utopia-bay/'.$folder, false);
    }

    public function migrateSlipFile(string $absolutePath): string
    {
        return $this->upload($absolutePath, 'utopia-bay/payment-slips', true);
    }

    public function publicUrl(string $path): string
    {
        $asset = $this->cloudinaryAsset($path);

        return $asset
            ? (string) $this->client()->image($asset['public_id'].'.'.$asset['format'])->toUrl()
            : Storage::disk('public')->url($this->localPath($path));
    }

    public function publicExists(string $path): bool
    {
        $asset = $this->cloudinaryAsset($path);

        return $asset ? $this->cloudinaryExists($path) : Storage::disk('public')->exists($this->localPath($path));
    }

    public function slipExists(string $path): bool
    {
        $asset = $this->cloudinaryAsset($path);

        return $asset
            ? (str_starts_with($asset['public_id'], 'utopia-bay/payment-slips/') && $this->cloudinaryExists($path))
            : Storage::disk('local')->exists($path);
    }

    public function slipDownloadUrl(string $path): string
    {
        $asset = $this->cloudinaryAsset($path);
        if (! $asset || ! str_starts_with($asset['public_id'], 'utopia-bay/payment-slips/')) {
            throw new RuntimeException('Invalid protected media identifier.');
        }

        return $this->client()->uploadApi()->privateDownloadUrl($asset['public_id'], $asset['format'], [
            'type' => 'authenticated',
            'resource_type' => 'image',
            'expires_at' => now()->addMinutes(5)->timestamp,
            'attachment' => false,
        ]);
    }

    public function deletePublic(string $path): bool
    {
        $asset = $this->cloudinaryAsset($path);
        if (! $asset) {
            return Storage::disk('public')->delete($this->localPath($path));
        }
        if (! preg_match('~^utopia-bay/(?:rooms|room_types|facilities|venues|payment-settings)/[A-Za-z0-9/_-]+$~', $asset['public_id'])) {
            return false;
        }

        try {
            return ($this->client()->uploadApi()->destroy($asset['public_id'], ['resource_type' => 'image', 'type' => 'upload', 'invalidate' => true])['result'] ?? null) === 'ok';
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function deleteSlip(string $path): bool
    {
        $asset = $this->cloudinaryAsset($path);
        if (! $asset) {
            return Storage::disk('local')->delete($path);
        }
        if (! preg_match('~^utopia-bay/payment-slips/[A-Za-z0-9_-]+$~', $asset['public_id'])) {
            return false;
        }

        try {
            return ($this->client()->uploadApi()->destroy($asset['public_id'], ['resource_type' => 'image', 'type' => 'authenticated', 'invalidate' => true])['result'] ?? null) === 'ok';
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function isCloudinary(string $path): bool
    {
        return $this->cloudinaryAsset($path) !== null;
    }

    private function upload(string $absolutePath, string $folder, bool $authenticated): string
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new RuntimeException('The local media source is unavailable.');
        }
        $folder = trim($folder, '/');
        $publicId = $folder.'/'.Str::uuid();
        $response = $this->client()->uploadApi()->upload($absolutePath, [
            'public_id' => $publicId,
            'type' => $authenticated ? 'authenticated' : 'upload',
            'resource_type' => 'image',
            'overwrite' => false,
        ]);
        $id = $response['public_id'] ?? null;
        $format = $response['format'] ?? null;
        if (! is_string($id) || $id !== $publicId || ! is_string($format) || ! preg_match('/^[A-Za-z0-9]+$/', $format)) {
            throw new RuntimeException('Cloudinary returned an invalid asset identifier.');
        }

        return 'cloudinary:'.$id.'.'.$format;
    }

    private function cloudinaryAsset(string $path): ?array
    {
        if (! preg_match('~^cloudinary:(utopia-bay/[A-Za-z0-9/_-]+)\.([A-Za-z0-9]+)$~', $path, $matches)) {
            return null;
        }

        return ['public_id' => $matches[1], 'format' => $matches[2]];
    }

    private function cloudinaryExists(string $path): bool
    {
        $asset = $this->cloudinaryAsset($path);
        try {
            $this->client()->adminApi()->asset($asset['public_id'], [
                'resource_type' => 'image',
                'type' => str_starts_with($asset['public_id'], 'utopia-bay/payment-slips/') ? 'authenticated' : 'upload',
            ]);

            return true;
        } catch (\Cloudinary\Api\Exception\NotFound $exception) {
            return false;
        }
    }

    private function localPath(string $path): string
    {
        return str_starts_with($path, '/storage/') ? substr($path, strlen('/storage/')) : $path;
    }

    private function client(): Cloudinary
    {
        if ($this->cloudinary) {
            return $this->cloudinary;
        }
        $credentials = config('media.cloudinary');
        if (blank($credentials['cloud_name'] ?? null)
            || blank($credentials['api_key'] ?? null)
            || blank($credentials['api_secret'] ?? null)) {
            throw new RuntimeException('Cloudinary credentials are not configured.');
        }

        return $this->cloudinary = new Cloudinary(['cloud' => $credentials]);
    }
}
