<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEventReservationStatusRequest;
use App\Models\EventBooking;
use App\Models\Venue;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EventReservationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', EventBooking::class);

        $status = $request->string('status')->toString();

        $eventBookings = EventBooking::query()
            ->with(['user', 'venue'])
            ->when(
                in_array($status, EventBooking::STATUSES, true),
                fn ($query) => $query->where('status', $status),
            )
            ->when(
                $request->filled('venue_id'),
                fn ($query) => $query->where('venue_id', $request->integer('venue_id')),
            )
            ->when(
                $request->filled('event_date'),
                fn ($query) => $query->whereDate('starts_at', $request->string('event_date')->toString()),
            )
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('management.event-reservations.index', [
            'eventBookings' => $eventBookings,
            'statuses' => EventBooking::STATUSES,
            'venues' => Venue::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(EventBooking $eventBooking): View
    {
        Gate::authorize('view', $eventBooking);

        $eventBooking->load(['user', 'venue', 'processedBy']);

        return view('management.event-reservations.show', ['eventBooking' => $eventBooking]);
    }

    public function approve(
        UpdateEventReservationStatusRequest $request,
        EventBooking $eventBooking,
    ): RedirectResponse {
        Gate::authorize('approve', $eventBooking);

        try {
            $eventBooking->approve($request->user(), $request->statusNote());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Event reservation approved.');
    }

    public function reject(
        UpdateEventReservationStatusRequest $request,
        EventBooking $eventBooking,
    ): RedirectResponse {
        Gate::authorize('reject', $eventBooking);

        try {
            $eventBooking->reject($request->user(), $request->statusNote());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Event reservation rejected.');
    }

    public function cancel(
        UpdateEventReservationStatusRequest $request,
        EventBooking $eventBooking,
    ): RedirectResponse {
        Gate::authorize('cancel', $eventBooking);

        try {
            $eventBooking->cancel($request->user(), $request->statusNote());
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Event reservation cancelled.');
    }
}
