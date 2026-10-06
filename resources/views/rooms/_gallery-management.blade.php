@php($editing = isset($room))
@php($photoCount = $editing ? $room->images->count() : 0)
<section class="room-photos mt-4" aria-labelledby="room-photos-heading">
<div class="d-flex align-items-center justify-content-between mb-1"><h2 id="room-photos-heading" class="h5 mb-0">Room Photos</h2><span class="room-photos-count">{{ $photoCount }} / 10</span></div>
<p class="small text-muted mb-3">Show guests what this room looks like.</p>
@if($editing)<form id="photo-upload-form" method="POST" enctype="multipart/form-data" action="{{ route('management.rooms.images.store', $room) }}">@csrf</form>@endif
<div class="room-photos-upload">
    <div class="d-flex justify-content-between flex-wrap gap-1 mb-2"><label for="images" class="small fw-semibold">Upload room photos</label><span id="room-photo-help" class="small text-muted">JPG, PNG or WebP · Max 2 MB · Up to 5 at once</span></div>
    <div class="room-upload-row"><input id="images" form="{{ $editing ? 'photo-upload-form' : 'room-form' }}" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm @error('images') is-invalid @enderror" data-preview-target="room-image-preview" aria-describedby="room-photo-help photo-selection-status" @disabled($photoCount >= 10)>@if($editing)<button type="submit" form="photo-upload-form" class="btn btn-hotel btn-sm" @disabled($photoCount >= 10)>Upload</button>@endif</div>
    <p id="photo-selection-status" class="small text-muted mt-2 mb-0" aria-live="polite">@if($photoCount >= 10)All 10 photo slots are used.@elseif($editing)Photo actions save separately from room details.@else Photos upload when you create the room.@endif</p>
</div>
<div id="room-image-preview" class="image-preview-grid"></div>
<h3 class="h6 mt-4 mb-3">Gallery</h3>
<div class="room-photo-grid">
@forelse($editing ? $room->images : [] as $photo)
    <article class="room-photo-card">
        <div class="room-photo-preview"><img src="{{ $photo->url() }}" alt="{{ $photo->alt_text ?: 'Room '.$room->room_number }}" loading="lazy">@if($photo->is_primary)<span class="room-photo-cover">★ Cover</span>@endif</div>
        <div class="room-photo-controls">
            <form method="POST" action="{{ route('management.rooms.images.update', [$room, $photo]) }}">@csrf @method('PATCH')
                <label for="alt-{{ $photo->id }}" class="form-label small mb-1">Alt text <span class="text-muted">(optional)</span></label>
                <input id="alt-{{ $photo->id }}" name="alt_text" maxlength="191" value="{{ $photo->alt_text }}" class="form-control form-control-sm mb-2" placeholder="Describe this photo">
                <div class="d-flex align-items-end gap-2"><div class="room-photo-order"><label for="order-{{ $photo->id }}" class="form-label small mb-1">Order</label><input id="order-{{ $photo->id }}" name="sort_order" type="number" min="0" max="9999" required value="{{ $photo->sort_order }}" class="form-control form-control-sm"></div><button class="btn btn-outline-secondary btn-sm" aria-label="Save photo details">Save</button></div>
            </form>
            <div class="room-photo-actions">
                @unless($photo->is_primary)<form method="POST" action="{{ route('management.rooms.images.primary', [$room, $photo]) }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary btn-sm" aria-label="Use as cover">Set as cover</button></form>@else<span class="small text-muted">★ Cover</span>@endunless
                <form method="POST" action="{{ route('management.rooms.images.destroy', [$room, $photo]) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger room-photo-delete" data-confirm="Delete this photo?">Delete photo</button></form>
            </div>
        </div>
    </article>
@empty
    <div class="room-photos-empty"><i class="bi bi-images" aria-hidden="true"></i><p class="fw-semibold mb-1">No photos added yet</p><p class="small text-muted mb-0">Add photos to showcase this room.</p><button type="button" class="btn btn-outline-secondary btn-sm mt-2" data-choose-room-photos>Choose photos</button></div>
@endforelse
</div>
</section>
