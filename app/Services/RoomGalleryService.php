<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RoomGalleryService
{
    // The caller supplies its room changes so uploads and those changes commit together.
    public function save(callable $saveRoom, array $files): Room
    {
        $written = [];
        try {
            return DB::transaction(function () use ($saveRoom, $files, &$written) {
                $room = $saveRoom();
                $room = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
                $count = $room->images()->count();
                if (count($files) > 5 || $count + count($files) > 10) {
                    throw ValidationException::withMessages(['images' => 'Upload up to 5 new images at a time, with no more than 10 gallery images per room.']);
                }
                $order = ($room->images()->max('sort_order') ?? -1) + 1;
                foreach ($files as $index => $file) {
                    $path = $file->store('rooms/gallery/'.$room->id, 'public');
                    if (! $path) {
                        throw new RuntimeException('Unable to store room image.');
                    }
                    $written[] = $path;
                    // At the ordering limit, ties retain upload order through the ID tie-breaker.
                    $room->images()->create(['image_path' => $path, 'sort_order' => min(9999, $order + $index), 'is_primary' => $count === 0 && $index === 0]);
                }

                return $room;
            });
        } catch (Throwable $exception) {
            foreach ($written as $path) {
                $this->removeFile($path);
            }
            throw $exception;
        }
    }

    public function primary(Room $room, int $imageId): void
    {
        DB::transaction(function () use ($room, $imageId) {
            Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $image = $room->images()->findOrFail($imageId);
            $room->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });
    }

    public function deleteImage(Room $room, int $imageId): void
    {
        DB::transaction(function () use ($room, $imageId) {
            Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $image = $room->images()->findOrFail($imageId);
            $path = $image->image_path;
            $image->delete();
            if (! $room->images()->where('is_primary', true)->exists()) {
                $room->images()->first()?->update(['is_primary' => true]);
            }
            DB::afterCommit(fn () => $this->cleanup([$path]));
        });
    }

    public function cleanup(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            // Only generated gallery filenames are owned by this feature.
            if (! preg_match('~^rooms/gallery/[0-9]+/[A-Za-z0-9]+\.(jpg|jpeg|png|webp)$~i', $path)) {
                continue;
            }
            if (RoomImage::where('image_path', $path)->exists() || Room::where('image', $path)->exists()) {
                continue;
            }
            $this->removeFile($path);
        }
    }

    private function removeFile(string $path): void
    {
        try {
            if (! Storage::disk('public')->delete($path)) {
                report(new RuntimeException('Unable to remove gallery file: '.$path));
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
