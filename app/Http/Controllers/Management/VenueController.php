<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVenueRequest;
use App\Http\Requests\UpdateVenueRequest;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Venue::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $eventType = $request->string('event_type')->toString();

        $venues = Venue::query()
            ->withCount('eventBookings')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'archived', fn ($query) => $query->where('is_active', false))
            ->when(
                in_array($eventType, Venue::EVENT_TYPES, true),
                fn ($query) => $query->whereJsonContains('event_types', $eventType),
            )
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('management.venues.index', [
            'venues' => $venues,
            'eventTypes' => Venue::EVENT_TYPES,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Venue::class);

        return view('management.venues.create', [
            'eventTypes' => Venue::EVENT_TYPES,
        ]);
    }

    public function store(StoreVenueRequest $request): RedirectResponse
    {
        $data = $request->safe()->only([
            'name',
            'description',
            'location',
            'capacity',
            'price',
            'event_types',
        ]);
        $data['images'] = $this->storeImages($request);
        $data['is_active'] = true;

        $venue = Venue::create($data);

        return redirect()
            ->route('management.venues.show', $venue)
            ->with('success', 'Venue created successfully.');
    }

    public function show(Venue $venue): View
    {
        Gate::authorize('view', $venue);

        $venue->loadCount('eventBookings');
        $venue->load([
            'eventBookings' => fn ($query) => $query
                ->with('user')
                ->orderByDesc('starts_at')
                ->limit(10),
        ]);

        return view('management.venues.show', ['venue' => $venue]);
    }

    public function edit(Venue $venue): View
    {
        Gate::authorize('update', $venue);

        return view('management.venues.edit', [
            'venue' => $venue,
            'eventTypes' => Venue::EVENT_TYPES,
        ]);
    }

    public function update(UpdateVenueRequest $request, Venue $venue): RedirectResponse
    {
        $validated = $request->validated();
        $removedImages = $validated['remove_images'] ?? [];
        $images = array_values(array_diff($venue->images ?? [], $removedImages));
        $images = [...$images, ...$this->storeImages($request)];

        $venue->update([
            ...Arr::only($validated, [
                'name',
                'description',
                'location',
                'capacity',
                'price',
                'event_types',
            ]),
            'images' => $images,
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($removedImages !== []) {
            Storage::disk('public')->delete($removedImages);
        }

        return redirect()
            ->route('management.venues.show', $venue)
            ->with('success', 'Venue updated successfully.');
    }

    public function destroy(Venue $venue): RedirectResponse
    {
        Gate::authorize('delete', $venue);

        $venue->update(['is_active' => false]);

        return redirect()
            ->route('management.venues.index')
            ->with('success', 'Venue archived. Existing reservation history was preserved.');
    }

    /**
     * @return list<string>
     */
    private function storeImages(Request $request): array
    {
        $paths = [];

        foreach (Arr::wrap($request->file('images', [])) as $image) {
            $paths[] = $image->store('venues', 'public');
        }

        return $paths;
    }
}
