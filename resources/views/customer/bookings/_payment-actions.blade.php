@if($state['url'])<a class="btn btn-sm btn-hotel" href="{{ $state['url'] }}">{{ $state['label'] }}</a>@endif
@if($state['payment']?->status === 'Paid')<a class="btn btn-sm btn-outline-primary" href="{{ route('customer.payments.receipt', $state['payment']) }}">Receipt</a>@endif
