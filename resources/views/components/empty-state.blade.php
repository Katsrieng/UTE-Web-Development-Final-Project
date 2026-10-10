@props(['icon' => 'bi-inbox', 'title' => 'Nothing here yet', 'message' => ''])
<div class="text-center text-muted py-5">
    <i class="bi {{ $icon }} fs-1 d-block mb-2"></i>
    <h5 class="mb-1">{{ $title }}</h5>
    @if($message)<p class="mb-0">{{ $message }}</p>@endif
</div>