@props(['icon' => 'bi-inbox', 'title', 'message' => null])

<div {{ $attributes->class(['empty-state']) }}>
    <span class="empty-state-icon"><i class="bi {{ $icon }}"></i></span>
    <h2>{{ $title }}</h2>
    @if($message)<p>{{ $message }}</p>@endif
    {{ $slot }}
</div>
