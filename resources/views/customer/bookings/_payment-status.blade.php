<div class="booking-payment-state">
<div class="d-flex flex-wrap align-items-center gap-2"><small class="text-muted">Payment</small>@if($state['payment'])<x-status-badge :status="$state['payment']->status" />@endif<strong>{{ $state['paymentMessage'] }}</strong></div>
@if($state['help'])<p class="small text-muted mb-0 mt-1">{{ $state['help'] }}</p>@endif
</div>
