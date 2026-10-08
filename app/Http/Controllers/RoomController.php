<?php

namespace App\Http\Controllers;

use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\RoomGalleryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with(['roomType', 'facilities', 'images']);
        $roomTypes = $filterFacilities = collect();
        if ($request->routeIs('management.rooms.index')) {
            if (in_array($request->query('status'), ['available', 'occupied', 'cleaning', 'booked', 'maintenance'], true)) {
                $query->where('status', $request->query('status'));
            }
        } else {
            $request->validate([
                'room_type_id' => ['nullable', 'integer', 'exists:room_types,id'],
                'guests' => ['nullable', 'integer', 'min:1', 'max:100000'],
                'facility_id' => ['nullable', 'integer', 'exists:facilities,id'],
                'min_price' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
                'max_price' => ['nullable', 'numeric', 'min:0', 'max:100000000', ...($request->filled('min_price') ? ['gte:min_price'] : [])],
            ]);
            $term = \App\Support\ListFilters::term($request);
            $query->when($term !== '', fn ($query) => $query->whereHas('roomType', fn ($types) => $types->where('name', 'like', "%{$term}%")))
                ->when($request->filled('room_type_id'), fn ($query) => $query->where('room_type_id', $request->integer('room_type_id')))
                ->when($request->filled('guests'), fn ($query) => $query->whereHas('roomType', fn ($types) => $types->where('capacity', '>=', $request->integer('guests'))))
                ->when($request->filled('facility_id'), fn ($query) => $query->whereHas('facilities', fn ($facilities) => $facilities->where('facilities.id', $request->integer('facility_id'))))
                ->when($request->filled('min_price'), fn ($query) => $query->where('price_per_night', '>=', $request->query('min_price')))
                ->when($request->filled('max_price'), fn ($query) => $query->where('price_per_night', '<=', $request->query('max_price')));
            $roomTypes = RoomType::orderBy('name')->get(['id', 'name']);
            $filterFacilities = Facility::whereHas('rooms')->orderBy('name')->get(['id', 'name']);
        }
        $rooms = $query->latest()->paginate(10)->withQueryString();

        return view('rooms.index', compact('rooms', 'roomTypes', 'filterFacilities'));
    }

    public function create()
    {
        $roomTypes = RoomType::all();
        $facilities = Facility::all();

        return view('rooms.create', compact('roomTypes', 'facilities'));
    }

    public function store(Request $request, RoomGalleryService $gallery)
    {
        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:50|unique:rooms,room_number',
            'floor' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'status' => 'required|in:available,booked,occupied,maintenance,cleaning',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $files = $request->file('images', []);
        if ($request->hasFile('image')) {
            $files[] = $request->file('image');
        }
        unset($validated['image'], $validated['images'], $validated['facilities']);
        $gallery->save(function () use ($validated, $request) {
            $room = Room::create($validated);
            $room->facilities()->sync($request->input('facilities', []));

            return $room;
        }, $files);

        return redirect()->route('management.rooms.index')->with('success', 'Room created successfully.');
    }

    public function show(Room $room)
    {
        $room->load(['roomType', 'facilities', 'images']);

        return view('rooms.show', compact('room'));
    }

    public function edit(Room $room)
    {
        $roomTypes = RoomType::all();
        $facilities = Facility::all();
        $room->load(['facilities', 'images']);

        return view('rooms.edit', compact('room', 'roomTypes', 'facilities'));
    }

    public function update(Request $request, Room $room, RoomGalleryService $gallery)
    {
        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:50|unique:rooms,room_number,'.$room->id,
            'floor' => 'required|integer|min:1',
            'price_per_night' => 'required|numeric|min:0',
            'status' => 'required|in:available,booked,occupied,maintenance,cleaning',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'images' => 'nullable|array|max:5',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
        ]);

        $files = $request->file('images', []);
        if ($request->hasFile('image')) {
            $files[] = $request->file('image');
        }
        unset($validated['image'], $validated['images'], $validated['facilities']);
        $gallery->save(function () use ($room, $validated, $request) {
            $locked = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $locked->update($validated);
            $locked->facilities()->sync($request->input('facilities', []));

            return $locked;
        }, $files);

        return redirect()->route('management.rooms.index')->with('success', 'Room #'.$room->room_number.' updated successfully!');
    }

    public function destroy(Room $room, RoomGalleryService $gallery)
    {
        DB::transaction(function () use ($room, $gallery) {
            $locked = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();
            $paths = $locked->images()->pluck('image_path')->all();
            $locked->delete();
            DB::afterCommit(fn () => $gallery->cleanup($paths));
        });

        return redirect()->route('management.rooms.index')->with('success', 'Room deleted successfully.');
    }
}
