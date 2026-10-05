@props(['status'])

@php
    $normalizedStatus = strtolower((string) $status);
    $statusClass = match($normalizedStatus) {
        'active', 'available', 'approved', 'paid', 'completed' => 'status-success',
        'pending', 'cleaning' => 'status-warning',
        'rejected', 'failed', 'disabled' => 'status-danger',
        'booked', 'occupied', 'processing' => 'status-info',
        'maintenance', 'refunded', 'cancelled', 'archived', 'inactive' => 'status-neutral',
        default => 'status-neutral',
    };
@endphp

<span {{ $attributes->class(['status-badge', $statusClass]) }}><span class="status-dot"></span>{{ ucfirst($normalizedStatus) }}</span>
