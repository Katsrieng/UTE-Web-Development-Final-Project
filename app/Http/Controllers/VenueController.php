<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VenueController extends Controller
{
    public function index(Request $request): View
    {
        $eventType = $request->string('event_type')->toString();

        $venues = Venue::query()
            ->active()
            ->when(
                in_array($eventType, Venue::EVENT_TYPES, true),
                fn ($query) => $query->whereJsonContains('event_types', $eventType),
            )
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('venues.index', [
            'venues' => $venues,
            'eventTypes' => Venue::EVENT_TYPES,
        ]);
    }

    public function show(Venue $venue): View
    {
        abort_unless($venue->is_active, 404);

        return view('venues.show', ['venue' => $venue]);
    }
}
