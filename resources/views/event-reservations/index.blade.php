@extends('layouts.app')
@section('title', 'My Event Reservations')
@section('content')
<section class="page-hero"><div class="container"><p class="section-kicker">Your events</p><h1>My reservations</h1><p>Track requests, review details, and see the latest status from hotel staff.</p></div></section>
<section class="content-section compact"><div class="container">
    <div class="page-heading"><div><h1>Event reservations</h1><p>{{ $eventBookings->total() }} reservation{{ $eventBookings->total() === 1 ? '' : 's' }} in your account.</p></div><a href="{{ route('event-reservations.create') }}" class="btn btn-hotel"><i class="bi bi-plus-lg me-1"></i> New Reservation</a></div>
    <x-list-toolbar :action="route('event-reservations.index')" placeholder="Reservation ID or venue" :fields="['status'=>['label'=>'Status','all'=>'All statuses','options'=>array_combine(App\Models\EventBooking::STATUSES, array_map('ucfirst',App\Models\EventBooking::STATUSES))], 'venue_id'=>['label'=>'Venue','all'=>'All venues','options'=>$venues->pluck('name','id')->all()]]" />
    <div class="table-card"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th class="ps-4">Venue</th><th>Event</th><th>Date &amp; time</th><th>Guests</th><th>Status</th><th class="text-end pe-4">Action</th></tr></thead><tbody>
    @forelse($eventBookings as $eventBooking)<tr><td class="ps-4"><strong>{{ $eventBooking->venue->name }}</strong><small class="d-block text-muted">Reservation #{{ $eventBooking->id }}</small></td><td>{{ ucfirst($eventBooking->event_type) }}</td><td>{{ $eventBooking->starts_at->format('M j, Y') }}<small class="d-block text-muted">{{ $eventBooking->starts_at->format('g:i A') }}–{{ $eventBooking->ends_at->format('g:i A') }}</small></td><td>{{ number_format($eventBooking->guest_count) }}</td><td><x-status-badge :status="$eventBooking->status" /></td><td class="text-end pe-4"><a href="{{ route('event-reservations.show',$eventBooking) }}" class="btn btn-sm btn-outline-primary">View details</a></td></tr>
    @empty<tr><td colspan="6"><x-list-no-results module="event reservations" :clear="route('event-reservations.index')" /></td></tr>@endforelse
    </tbody></table></div></div>
    @if($eventBookings->hasPages())<div class="mt-4">{{ $eventBookings->links('pagination::bootstrap-5') }}</div>@endif
</div></section>
@endsection
