<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventReservationRequest;
use App\Models\EventBooking;
use App\Models\Venue;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EventReservationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EventBooking::class);

        $eventBookings = EventBooking::query()
            ->whereBelongsTo($request->user())
            ->with('venue')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(10);

        return view('event-reservations.index', ['eventBookings' => $eventBookings]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', EventBooking::class);

        return view('event-reservations.create', [
            'venues' => Venue::query()->active()->orderBy('name')->get(),
            'eventTypes' => Venue::EVENT_TYPES,
            'selectedVenueId' => $request->integer('venue'),
        ]);
    }

    public function store(StoreEventReservationRequest $request): RedirectResponse
    {
        Gate::authorize('create', EventBooking::class);

        try {
            $eventBooking = EventBooking::reserve(
                $request->user(),
                Venue::findOrFail($request->integer('venue_id')),
                $request->bookingData(),
            );
        } catch (DomainException $exception) {
            throw ValidationException::withMessages([
                'schedule' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('event-reservations.show', $eventBooking)
            ->with('success', 'Your event reservation was submitted and is awaiting review.');
    }

    public function show(Request $request, EventBooking $eventBooking): View
    {
        abort_unless($eventBooking->user_id === $request->user()->getKey(), 404);
        Gate::authorize('view', $eventBooking);

        $eventBooking->load(['venue', 'processedBy']);

        return view('event-reservations.show', ['eventBooking' => $eventBooking]);
    }

    public function cancel(Request $request, EventBooking $eventBooking): RedirectResponse
    {
        abort_unless($eventBooking->user_id === $request->user()->getKey(), 404);
        Gate::authorize('cancel', $eventBooking);

        try {
            $eventBooking->cancel($request->user());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('event-reservations.show', $eventBooking)
            ->with('success', 'Your event reservation was cancelled.');
    }
}
