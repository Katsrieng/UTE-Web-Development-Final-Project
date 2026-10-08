@if($loyalty['account'] || $loyalty['membership'])
    @php
        $member = $loyalty['membership'];
        $type = $member?->membershipType;
        $next = $type?->nextMembershipType;
        $threshold = $type?->loyalty_upgrade_points;
        $visiblePoints = ($staffContext ?? false) ? $loyalty['balance'] : max(0, $loyalty['balance']);
        $progress = $threshold ? min(100, max(0, $loyalty['balance']) * 100 / $threshold) : 0;
    @endphp
    <section class="hotel-card loyalty-panel p-3 p-md-4 mb-4" aria-label="Membership loyalty">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div><p class="section-kicker mb-1">{{ ($staffContext ?? false) ? 'Customer loyalty' : 'Your loyalty rewards' }}</p><h2 class="h5 mb-1">{{ $type ? $type->name.' Member' : 'Loyalty account' }}</h2>@if($member)<p class="small text-muted mb-0">Membership expiry · {{ $member->end_date->format('M j, Y') }}</p>@endif</div>
            <div class="text-sm-end"><strong class="loyalty-balance">{{ number_format($visiblePoints) }} <span>points</span></strong><span class="d-block small text-muted">{{ $loyalty['active'] ? 'Active rewards' : 'Frozen rewards' }}</span></div>
        </div>
        @if(! $loyalty['active'])
            <p class="small text-muted mb-3">{{ $member && $member->end_date->lt(today()) ? 'Points frozen until membership renewal' : 'Points are frozen while no eligible paid membership is active.' }}</p>
        @elseif($next && $next->status === 'active' && $threshold)
            <div class="d-flex justify-content-between flex-wrap gap-2 small mb-2"><span>{{ number_format(max(0, $threshold - $loyalty['balance'])) }} points to {{ $next->name }}</span><span class="text-muted">{{ number_format($loyalty['usable']) }} / {{ number_format($threshold) }}</span></div>
            <div class="progress loyalty-progress mb-3" role="progressbar" aria-label="Progress to {{ $next->name }}" aria-valuemin="0" aria-valuemax="{{ $threshold }}" aria-valuenow="{{ min($threshold, $loyalty['usable']) }}"><div class="progress-bar" style="width:{{ $progress }}%"></div></div>
        @elseif(! $threshold)
            <p class="small mb-3"><i class="bi bi-award me-1" aria-hidden="true"></i>Highest tier reached</p>
        @endif
        @if($loyalty['balance'] < 0)<p class="small text-muted mb-3">Refund adjustment balance: {{ number_format($loyalty['balance']) }} points. Future earnings settle this adjustment before more points become usable.</p>@endif
        <div class="border-top pt-3">
            <h3 class="h6 mb-2">Recent point activity</h3>
            @forelse($loyalty['activity'] as $entry)
                <div class="loyalty-entry d-flex justify-content-between align-items-start gap-3 py-2">
                    <div><span class="small">@if($entry->kind === 'upgrade')Upgraded {{ $entry->old_tier_name }} → {{ $entry->new_tier_name }}@elseif($entry->kind === 'refund')Refund adjustment · Booking #{{ $entry->payment?->booking_id }}@else Booking #{{ $entry->payment?->booking_id }}@endif</span><small class="d-block text-muted">{{ $entry->created_at->format('M j, Y') }}@if(($staffContext ?? false) && $entry->payment) · <a href="{{ route('payments.show', $entry->payment) }}">{{ $entry->payment->reference_number }}</a>@endif</small></div>
                    <strong class="small text-nowrap {{ $entry->points > 0 ? 'text-success' : 'loyalty-adjustment' }}">{{ $entry->points > 0 ? '+' : '' }}{{ number_format($entry->points) }} points</strong>
                </div>
            @empty
                <p class="small text-muted mb-0">Points appear here after eligible Booking payments are Paid.</p>
            @endforelse
        </div>
    </section>
@endif
