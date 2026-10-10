@props(['status' => 'inactive'])
@php
    $class = match (strtolower($status)) {
        'active', 'completed', 'paid' => 'bg-success',
        'pending' => 'bg-warning text-dark',
        'cancelled', 'expired', 'failed' => 'bg-danger',
        default => 'bg-secondary',
    };
@endphp
<span class="badge {{ $class }}">{{ ucfirst($status) }}</span>