<?php

namespace App\Http\Controllers;

use App\Models\RoomImage;
use App\Models\RoomType;
use App\Services\RoomGalleryService;
use App\Services\MediaStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomTypeController extends Controller
{
    public function __construct(private MediaStorage $media) {}
    /**
     * Display a listing of room types.
     */
    public function index()
    {
        $roomTypes = RoomType::withCount('rooms')->latest()->paginate(10);

        return view('room_types.index', compact('roomTypes'));
    }

    /**
     * Show the form for creating a new room type.
     */
    public function create()
    {
        return view('room_types.create');
    }

    /**
     * Store a newly created room type in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $this->media->storePublic($request->file('image'), 'room_types');
            $validated['image'] = $this->media->isCloudinary($path) ? $path : '/storage/'.$path;
        }

        try {
            RoomType::create($validated);
        } catch (\Throwable $exception) {
            if ($path) { $this->media->deletePublic($path); }
            throw $exception;
        }

        return redirect()->route('management.room-types.index')->with('success', 'Room Type created successfully.');
    }

    /**
     * Display the specified room type.
     */
    public function show(RoomType $roomType)
    {
        $roomType->load('rooms');

        return view('room_types.show', compact('roomType'));
    }

    /**
     * Show the form for editing the specified room type.
     */
    public function edit(RoomType $roomType)
    {
        return view('room_types.edit', compact('roomType'));
    }

    /**
     * Update the specified room type in storage.
     */
    public function update(Request $request, RoomType $roomType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:room_types,name,'.$roomType->id,
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'bed_type' => 'nullable|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $path = null;
        $oldPath = $roomType->image;
        if ($request->hasFile('image')) {
            $path = $this->media->storePublic($request->file('image'), 'room_types');
            $validated['image'] = $this->media->isCloudinary($path) ? $path : '/storage/'.$path;
        }

        try {
            DB::transaction(function () use ($roomType, $validated, $path, $oldPath): void {
                $roomType->update($validated);
                if ($path && $oldPath && $this->ownedImage($oldPath)) {
                    DB::afterCommit(fn () => $this->media->deletePublic($oldPath));
                }
            });
        } catch (\Throwable $exception) {
            if ($path) { $this->media->deletePublic($path); }
            throw $exception;
        }

        return redirect()->route('management.room-types.index')->with('success', 'Room Type updated successfully.');
    }

    /**
     * Remove the specified room type from storage.
     */
    public function destroy(RoomType $roomType, RoomGalleryService $gallery)
    {
        DB::transaction(function () use ($roomType, $gallery) {
            $locked = RoomType::whereKey($roomType->id)->lockForUpdate()->firstOrFail();
            $rooms = $locked->rooms()->orderBy('id')->lockForUpdate()->get();
            if (\App\Models\Booking::whereIn('room_id', $rooms->pluck('id'))->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'room_type' => 'This room type has booking history and cannot be deleted.',
                ]);
            }
            $paths = RoomImage::whereIn('room_id', $rooms->pluck('id'))->pluck('image_path')->all();
            $typeImage = $locked->image;
            $locked->delete();
            DB::afterCommit(function () use ($gallery, $paths, $typeImage) {
                $gallery->cleanup($paths);
                if ($typeImage && $this->ownedImage($typeImage)) {
                    $this->media->deletePublic($typeImage);
                }
            });
        });

        return redirect()->route('management.room-types.index')->with('success', 'Room Type deleted successfully.');
    }

    private function ownedImage(string $path): bool
    {
        return (bool) preg_match('~^(?:/storage/room_types/[A-Za-z0-9]+\.(?:jpg|jpeg|png|webp)|cloudinary:utopia-bay/room_types/[A-Za-z0-9-]+\.(?:jpg|jpeg|png|webp))$~i', $path);
    }
}
