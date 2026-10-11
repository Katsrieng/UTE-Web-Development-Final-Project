@props(['status', 'context' => null])

@php
    $normalizedStatus = strtolower((string) $status);
    $statusClass = match($normalizedStatus) {
        'active', 'available', 'approved', 'paid', 'completed', 'confirmed', 'open' => 'status-success',
        'pending', 'cleaning' => 'status-warning',
        'maintenance' => $context === 'facility' ? 'status-warning' : 'status-neutral',
        'rejected', 'failed', 'disabled', 'closed' => 'status-danger',
        'booked', 'occupied', 'processing', 'checked in' => 'status-info',
        'checked out' => 'status-complete',
        'cancelled' => 'status-cancelled',
        'refunded' => 'status-refunded',
        'archived', 'inactive' => 'status-neutral',
        default => 'status-neutral',
    };
@endphp

<span {{ $attributes->class(['status-badge', $statusClass]) }}><span class="status-dot"></span>{{ match($normalizedStatus) { 'checked in' => 'Checked In', 'checked out' => 'Checked Out', default => ucfirst($normalizedStatus) } }}</span>
