@php($editing = isset($room))
<section class="room-facilities mt-4" aria-label="Room facilities">
                <h2 class="h5 mb-3">Facilities</h2>
                @forelse($facilities as $facility)
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" form="room-form" name="facilities[]" value="{{ $facility->id }}" id="facility_{{ $facility->id }}" @checked(in_array($facility->id, old('facilities', $editing ? $room->facilities->pluck('id')->all() : [])))><label class="form-check-label" for="facility_{{ $facility->id }}">{{ $facility->name }}</label></div>
                @empty
                    <p class="text-muted small mb-0">No facilities are available yet.</p>
                @endforelse

</section>
