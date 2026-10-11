<?php

namespace App\Console\Commands;

use App\Models\BookingPaymentSlip;
use App\Models\Facility;
use App\Models\PaymentSetting;
use App\Models\Room;
use App\Models\RoomImage;
use App\Models\RoomType;
use App\Models\Venue;
use App\Services\BookingPaymentSlipService;
use App\Services\MediaStorage;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateMediaToCloudinary extends Command
{
    protected $signature = 'media:migrate-to-cloudinary {--dry-run : Inspect without uploading or updating records} {--delete-local : Delete unreferenced local sources after successful updates}';

    protected $description = 'Copy owned local media to Cloudinary and update existing path fields safely';

    private array $counts = [];

    private array $cleanupCandidates = [];

    public function handle(MediaStorage $media): int
    {
        $this->counts = ['migrated' => 0, 'would_migrate' => 0, 'skipped' => 0, 'missing' => 0, 'failed' => 0, 'local_deleted' => 0];
        $this->cleanupCandidates = [];
        $dryRun = (bool) $this->option('dry-run');

        RoomImage::query()->orderBy('id')->chunkById(100, function ($images) use ($media, $dryRun): void {
            foreach ($images as $image) {
                $this->migrateField($media, $image, 'image_path', 'room-gallery', 'rooms/gallery/'.$image->room_id, $dryRun);
            }
        });
        Room::query()->orderBy('id')->chunkById(100, function ($rooms) use ($media, $dryRun): void {
            foreach ($rooms as $room) {
                $this->migrateField($media, $room, 'image', 'room', 'rooms', $dryRun);
            }
        });
        RoomType::query()->orderBy('id')->chunkById(100, function ($types) use ($media, $dryRun): void {
            foreach ($types as $type) {
                $this->migrateField($media, $type, 'image', 'room-type', 'room_types', $dryRun);
            }
        });
        Facility::query()->orderBy('id')->chunkById(100, function ($facilities) use ($media, $dryRun): void {
            foreach ($facilities as $facility) {
                $this->migrateField($media, $facility, 'image', 'facility', 'facilities/'.$facility->id, $dryRun);
            }
        });
        PaymentSetting::query()->orderBy('id')->chunkById(100, function ($settings) use ($media, $dryRun): void {
            foreach ($settings as $setting) {
                $this->migrateField($media, $setting, 'khqr_image', 'payment-setting', 'payment-settings', $dryRun);
            }
        });
        Venue::query()->orderBy('id')->chunkById(100, function ($venues) use ($media, $dryRun): void {
            foreach ($venues as $venue) {
                foreach (array_unique($venue->images ?? []) as $path) {
                    $this->migrateVenueImage($media, $venue, $path, $dryRun);
                }
            }
        });
        BookingPaymentSlip::query()->orderBy('id')->chunkById(100, function ($slips) use ($media, $dryRun): void {
            foreach ($slips as $slip) {
                $this->migrateField($media, $slip, 'file_path', 'slip', 'payment-slips', $dryRun);
            }
        });

        if ($this->option('delete-local') && ! $dryRun) {
            $this->deleteUnreferencedSources();
        }

        $this->info('Migrated: '.$this->counts['migrated']);
        if ($dryRun) { $this->info('Would migrate (dry run): '.$this->counts['would_migrate']); }
        $this->info('Already Cloudinary / skipped: '.$this->counts['skipped']);
        $this->info('Missing local files: '.$this->counts['missing']);
        $this->info('Failed uploads or updates: '.$this->counts['failed']);
        if ($this->option('delete-local') && ! $dryRun) {
            $this->info('Unreferenced local files deleted: '.$this->counts['local_deleted']);
        } else {
            $this->line('Local source files retained.');
        }

        return $this->counts['failed'] > 0 || $this->counts['missing'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migrateField(MediaStorage $media, Model $record, string $field, string $kind, string $folder, bool $dryRun): void
    {
        $old = $record->getAttribute($field);
        if (! is_string($old) || $old === '') { return; }
        $source = $this->source($kind, $record, $old);
        if (! $source) { $this->skip($kind, $record->getKey()); return; }

        $this->migrate($media, $kind, $record, $old, $source, $folder, $dryRun, function (string $new) use ($record, $field, $old): bool {
            return DB::transaction(function () use ($record, $field, $old, $new): bool {
                $locked = $record->newQuery()->lockForUpdate()->find($record->getKey());
                if (! $locked || $locked->getAttribute($field) !== $old) { return false; }
                $locked->update([$field => $new]);

                return true;
            });
        });
    }

    private function migrateVenueImage(MediaStorage $media, Venue $venue, string $old, bool $dryRun): void
    {
        $source = $this->source('venue', $venue, $old);
        if (! $source) { $this->skip('venue', $venue->id); return; }

        $this->migrate($media, 'venue', $venue, $old, $source, 'venues', $dryRun, function (string $new) use ($venue, $old): bool {
            return DB::transaction(function () use ($venue, $old, $new): bool {
                $locked = Venue::query()->lockForUpdate()->find($venue->id);
                if (! $locked || ! in_array($old, $locked->images ?? [], true)) { return false; }
                $locked->update(['images' => array_map(fn ($path) => $path === $old ? $new : $path, $locked->images)]);

                return true;
            });
        });
    }

    private function migrate(MediaStorage $media, string $kind, Model $record, string $old, array $source, string $folder, bool $dryRun, callable $update): void
    {
        [$disk, $relative] = $source;
        if (! Storage::disk($disk)->exists($relative)) {
            $this->warn("{$kind} {$record->getKey()}: local file missing; record unchanged.");
            $this->counts['missing']++;
            return;
        }
        if ($dryRun) {
            $this->line("Would migrate {$kind} {$record->getKey()}.");
            $this->counts['would_migrate']++;
            return;
        }

        $new = null;
        try {
            $absolute = Storage::disk($disk)->path($relative);
            $new = $kind === 'slip' ? $media->migrateSlipFile($absolute) : $media->migratePublicFile($absolute, $folder);
            if (! $media->isCloudinary($new) || ! $update($new)) {
                throw new \RuntimeException('Cloudinary migration update was not completed.');
            }
        } catch (Throwable $exception) {
            if ($new && $media->isCloudinary($new)) {
                $kind === 'slip' ? $media->deleteSlip($new) : $media->deletePublic($new);
            }
            $this->error("{$kind} {$record->getKey()}: upload or update failed; record unchanged.");
            $this->counts['failed']++;
            return;
        }

        $this->info("Migrated {$kind} {$record->getKey()}.");
        $this->counts['migrated']++;
        $this->cleanupCandidates[$disk.'|'.$relative] = [$disk, $relative];
    }

    private function source(string $kind, Model $record, string $path): ?array
    {
        if (str_starts_with($path, 'cloudinary:')) { return null; }
        $relative = str_starts_with($path, '/storage/') ? substr($path, 9) : $path;
        $image = '[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)';
        $patterns = [
            'room-gallery' => '~^rooms/gallery/'.$record->room_id.'/'.$image.'$~i',
            'room' => '~^rooms/'.$image.'$~i',
            'room-type' => '~^room_types/'.$image.'$~i',
            'facility' => '~^facilities/'.$record->id.'/'.$image.'$~i',
            'venue' => '~^venues/'.$image.'$~i',
            'payment-setting' => '~^payment-settings/'.$image.'$~i',
        ];
        if ($kind === 'slip') {
            $disk = BookingPaymentSlipService::diskFor($path);
            return $disk && preg_match('~^(?:booking-payment-slips|payment-slips)/booking-'.$record->booking_id.'-[A-Za-z0-9]+\.(?:jpg|jpeg|png)$~i', $path)
                ? [$disk, $path] : null;
        }

        return preg_match($patterns[$kind], $relative) ? ['public', $relative] : null;
    }

    private function skip(string $kind, int $id): void
    {
        $this->line("Skipped {$kind} {$id}: already Cloudinary or not an owned local upload.");
        $this->counts['skipped']++;
    }

    private function deleteUnreferencedSources(): void
    {
        foreach ($this->cleanupCandidates as [$disk, $relative]) {
            if ($this->stillReferenced($disk, $relative)) {
                $this->warn("Retained {$disk} source {$relative}: another record still references it.");
                continue;
            }
            try {
                if (Storage::disk($disk)->delete($relative)) {
                    $this->counts['local_deleted']++;
                    continue;
                }
            } catch (Throwable $exception) {
                report($exception);
            }
            $this->warn("Unable to delete {$disk} source {$relative}; retained for manual review.");
        }
    }

    private function stillReferenced(string $disk, string $relative): bool
    {
        if ($disk === 'local') {
            return BookingPaymentSlip::where('file_path', $relative)->exists();
        }
        if (BookingPaymentSlip::where('file_path', $relative)->exists()
            || RoomImage::where('image_path', $relative)->exists()
            || Room::where('image', $relative)->exists()
            || RoomType::whereIn('image', [$relative, '/storage/'.$relative])->exists()
            || Facility::where('image', $relative)->exists()
            || PaymentSetting::where('khqr_image', $relative)->exists()) {
            return true;
        }
        foreach (Venue::query()->cursor() as $venue) {
            if (in_array($relative, $venue->images ?? [], true)) { return true; }
        }

        return false;
    }
}
