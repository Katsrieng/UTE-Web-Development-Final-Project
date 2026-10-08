<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEventReservationStatusRequest;
use App\Http\Requests\UpdateEventReservationRequest;
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

        $eventBookings = EventBooking::query()
            ->with(['user', 'venue'])->withExists('payments')
            ->tap(fn ($query) => \App\Support\ListFilters::events($query, $request, true))
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

        $eventBooking->load(['user', 'venue', 'processedBy'])->loadExists('payments');

        return view('management.event-reservations.show', ['eventBooking' => $eventBooking]);
    }

    public function edit(EventBooking $eventBooking): View|RedirectResponse
    {
        Gate::authorize('update', $eventBooking);
        if ($reason = $eventBooking->staffEditBlockReason()) {
            return redirect()->route('management.event-reservations.show', $eventBooking)->with('error', $reason);
        }
        return view('management.event-reservations.edit', [
            'eventBooking' => $eventBooking,
            'venues' => Venue::where(fn ($query) => $query->where('is_active', true)->orWhere('id', $eventBooking->venue_id))->orderBy('name')->get(),
            'eventTypes' => Venue::EVENT_TYPES,
        ]);
    }

    public function update(UpdateEventReservationRequest $request, EventBooking $eventBooking): RedirectResponse
    {
        Gate::authorize('update', $eventBooking);
        try {
            $eventBooking->updateByStaff(Venue::findOrFail($request->integer('venue_id')), $request->bookingData());
        } catch (DomainException $exception) {
            return back()->withErrors(['reservation' => $exception->getMessage()])->with('error', $exception->getMessage())->withInput();
        }
        return redirect()->route('management.event-reservations.show', $eventBooking)->with('success', 'Event reservation updated.');
    }

    public function destroy(EventBooking $eventBooking): RedirectResponse
    {
        Gate::authorize('delete', $eventBooking);
        try {
            $eventBooking->deleteByStaff();
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }
        return redirect()->route('management.event-reservations.index')->with('success', 'Event reservation deleted.');
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
