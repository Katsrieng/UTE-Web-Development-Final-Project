@extends('layouts.management')

@section('title', 'Dashboard')
@section('page-label', 'Dashboard')

@section('content')
@php
    $paidRate = $totalPayments > 0 ? round(($paidPayments / $totalPayments) * 100) : 0;
    $pendingRate = $totalPayments > 0 ? round(($pendingPayments / $totalPayments) * 100) : 0;
    $refundedRate = $totalPayments > 0 ? round(($refundedPayments / $totalPayments) * 100) : 0;
    $roomRevenueRate = $totalRevenue > 0 ? round(($roomBookingRevenue / $totalRevenue) * 100) : 0;
    $eventRevenueRate = $totalRevenue > 0 ? round(($eventBookingRevenue / $totalRevenue) * 100) : 0;
@endphp

<section class="dashboard-welcome">
    <div>
        <p class="section-kicker">Operations overview</p>
        <h1>Welcome back, {{ auth()->user()->name }}</h1>
        <p>Here is the latest snapshot of hotel payment activity.</p>
    </div>
    <div class="dashboard-welcome-actions">
        <span class="dashboard-date"><i class="bi bi-calendar3"></i>{{ now()->format('D, M j') }}</span>
        <a href="{{ route('payments.create') }}" class="btn btn-hotel">
            <i class="bi bi-plus-lg me-1"></i> Add Payment
        </a>
    </div>
</section>

<section class="row g-3 g-xl-4 mb-4" aria-label="Payment summary">
    <div class="col-sm-6 col-xl-3">
        <article class="dashboard-stat dashboard-stat--gold">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon"><i class="bi bi-cash-stack"></i></span>
                <span class="dashboard-stat-note">Paid revenue</span>
            </div>
            <p>Total revenue</p>
            <strong>${{ number_format($totalRevenue, 2) }}</strong>
            <small>From completed payments</small>
        </article>
    </div>
    <div class="col-sm-6 col-xl-3">
        <article class="dashboard-stat dashboard-stat--blue">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon"><i class="bi bi-receipt"></i></span>
                <span class="dashboard-stat-note">All records</span>
            </div>
            <p>Total payments</p>
            <strong>{{ number_format($totalPayments) }}</strong>
            <small>Across rooms and events</small>
        </article>
    </div>
    <div class="col-sm-6 col-xl-3">
        <article class="dashboard-stat dashboard-stat--green">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon"><i class="bi bi-check2-circle"></i></span>
                <span class="dashboard-stat-note">{{ $paidRate }}% of total</span>
            </div>
            <p>Paid payments</p>
            <strong>{{ number_format($paidPayments) }}</strong>
            <small>Successfully completed</small>
        </article>
    </div>
    <div class="col-sm-6 col-xl-3">
        <article class="dashboard-stat dashboard-stat--amber">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon"><i class="bi bi-hourglass-split"></i></span>
                <span class="dashboard-stat-note">{{ $pendingRate }}% of total</span>
            </div>
            <p>Pending payments</p>
            <strong>{{ number_format($pendingPayments) }}</strong>
            <small>Awaiting processing</small>
        </article>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <section class="dashboard-panel h-100">
            <header class="dashboard-panel-header">
                <div>
                    <p class="section-kicker mb-1">Payment health</p>
                    <h2>Payment status</h2>
                    <span>Distribution across all payment records</span>
                </div>
                <a href="{{ route('payments.index') }}" class="dashboard-panel-link">View payments <i class="bi bi-arrow-right"></i></a>
            </header>

            <div class="dashboard-panel-body dashboard-chart-area">
                @if($totalPayments > 0)
                    <div class="dashboard-chart-wrap">
                        <canvas id="paymentStatusChart" aria-label="Payment status chart"></canvas>
                    </div>
                    <div class="dashboard-chart-legend">
                        <div><span class="legend-dot legend-dot--paid"></span><span>Paid</span><strong>{{ $paidPayments }}</strong></div>
                        <div><span class="legend-dot legend-dot--pending"></span><span>Pending</span><strong>{{ $pendingPayments }}</strong></div>
                        <div><span class="legend-dot legend-dot--refunded"></span><span>Refunded</span><strong>{{ $refundedPayments }}</strong></div>
                    </div>
                @else
                    <div class="dashboard-empty-chart">
                        <span><i class="bi bi-pie-chart"></i></span>
                        <h3>No payment data yet</h3>
                        <p>The payment breakdown will appear after the first payment is recorded.</p>
                        <a href="{{ route('payments.create') }}" class="btn btn-outline-primary btn-sm">Record first payment</a>
                    </div>
                @endif
            </div>
        </section>
    </div>

    <div class="col-xl-4">
        <section class="dashboard-panel mb-4">
            <header class="dashboard-panel-header dashboard-panel-header--compact">
                <div>
                    <p class="section-kicker mb-1">Revenue mix</p>
                    <h2>Booking revenue</h2>
                </div>
            </header>
            <div class="dashboard-panel-body">
                <div class="revenue-row">
                    <span class="revenue-icon"><i class="bi bi-door-open"></i></span>
                    <div class="revenue-copy">
                        <span>Room bookings</span>
                        <strong>${{ number_format($roomBookingRevenue, 2) }}</strong>
                    </div>
                    <b>{{ $roomRevenueRate }}%</b>
                </div>
                <div class="progress revenue-progress" role="progressbar" aria-label="Room booking revenue" aria-valuenow="{{ $roomRevenueRate }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar revenue-progress-room" style="width: {{ $roomRevenueRate }}%"></div>
                </div>

                <div class="revenue-row mt-4">
                    <span class="revenue-icon revenue-icon--event"><i class="bi bi-calendar2-event"></i></span>
                    <div class="revenue-copy">
                        <span>Event bookings</span>
                        <strong>${{ number_format($eventBookingRevenue, 2) }}</strong>
                    </div>
                    <b>{{ $eventRevenueRate }}%</b>
                </div>
                <div class="progress revenue-progress" role="progressbar" aria-label="Event booking revenue" aria-valuenow="{{ $eventRevenueRate }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar revenue-progress-event" style="width: {{ $eventRevenueRate }}%"></div>
                </div>

                <div class="refund-summary">
                    <span><i class="bi bi-arrow-counterclockwise"></i> Refunded payments</span>
                    <strong>{{ number_format($refundedPayments) }} <small>({{ $refundedRate }}%)</small></strong>
                </div>
            </div>
        </section>

        <section class="dashboard-panel">
            <header class="dashboard-panel-header dashboard-panel-header--compact">
                <div>
                    <p class="section-kicker mb-1">Shortcuts</p>
                    <h2>Quick actions</h2>
                </div>
            </header>
            <div class="dashboard-quick-grid">
                <a href="{{ route('management.rooms.index') }}"><i class="bi bi-door-open"></i><span>Manage rooms</span></a>
                <a href="{{ route('management.venues.index') }}"><i class="bi bi-building"></i><span>Manage venues</span></a>
                <a href="{{ route('management.event-reservations.index') }}"><i class="bi bi-calendar2-check"></i><span>Event requests</span></a>
                <a href="{{ route('payments.index') }}"><i class="bi bi-credit-card"></i><span>All payments</span></a>
            </div>
        </section>
    </div>
</div>

<section class="dashboard-panel">
    <header class="dashboard-panel-header">
        <div>
            <p class="section-kicker mb-1">Latest activity</p>
            <h2>Recent payments</h2>
            <span>The five most recently recorded transactions</span>
        </div>
        <a href="{{ route('payments.index') }}" class="dashboard-panel-link">View all <i class="bi bi-arrow-right"></i></a>
    </header>
    <div class="table-responsive">
        <table class="table dashboard-table align-middle mb-0">
            <thead>
                <tr><th class="ps-4">Reference</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th class="text-end pe-4">Action</th></tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $payment)
                    <tr>
                        <td class="ps-4"><strong>{{ $payment->reference_number }}</strong><small>Payment #{{ $payment->id }}</small></td>
                        <td class="fw-semibold">${{ number_format($payment->amount, 2) }}</td>
                        <td>{{ $payment->payment_method }}</td>
                        <td><x-status-badge :status="$payment->status" /></td>
                        <td>{{ $payment->payment_date?->format('M j, Y') }}</td>
                        <td class="text-end pe-4"><a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="dashboard-table-empty">
                                <span><i class="bi bi-receipt"></i></span>
                                <div><strong>No payments recorded</strong><p>Add a payment to start tracking revenue and transaction activity.</p></div>
                                <a href="{{ route('payments.create') }}" class="btn btn-hotel btn-sm">Add payment</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@if($totalPayments > 0)
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script>
        const paymentChart = document.getElementById('paymentStatusChart');

        if (paymentChart && window.Chart) {
            new Chart(paymentChart, {
                type: 'doughnut',
                data: {
                    labels: ['Paid', 'Pending', 'Refunded'],
                    datasets: [{
                        data: [{{ $paidPayments }}, {{ $pendingPayments }}, {{ $refundedPayments }}],
                        backgroundColor: ['#2f856e', '#d6a565', '#7c8490'],
                        borderColor: '#ffffff',
                        borderWidth: 5,
                        hoverOffset: 5,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: { legend: { display: false } },
                },
            });
        }
    </script>
    @endpush
@endif
