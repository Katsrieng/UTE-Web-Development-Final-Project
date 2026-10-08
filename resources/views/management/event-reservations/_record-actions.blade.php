<div class="d-flex flex-wrap gap-2 {{ $compact ?? false ? 'justify-content-end' : '' }}">
@if(auth()->user()->can('update',$eventBooking) && !$eventBooking->staffEditBlockReason())

@staffroute('management.event-reservations.edit')
<a href="{{ route('management.event-reservations.edit', $eventBooking) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
@endstaffroute

@endif
@if(auth()->user()->can('delete',$eventBooking) && !$eventBooking->staffDeleteBlockReason())

@staffroute('management.event-reservations.destroy')
<form method="POST" action="{{ route('management.event-reservations.destroy', $eventBooking) }}" onsubmit="return confirm('Permanently delete this reservation? This cannot be undone.');">
@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
</form>
@endstaffroute

@endif
</div>
