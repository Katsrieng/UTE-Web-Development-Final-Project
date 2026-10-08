@extends('layouts.management')
@section('title', 'Payments')
@section('page-label', 'Payments')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Financial records</p><h1>Payments</h1><p>Review booking, membership and event payments, statuses, and receipts.</p></div><a href="{{ route('payments.create') }}" class="btn btn-hotel"><i class="bi bi-plus-lg me-1"></i> Add Payment</a></div>
<x-list-toolbar :action="route('payments.index')" placeholder="Customer, email, reference or booking ID" :fields="['status'=>['label'=>'Payment status','all'=>'All statuses','options'=>['Pending'=>'Pending','Paid'=>'Paid','Refunded'=>'Refunded']], 'payment_method'=>['label'=>'Method','all'=>'All methods','options'=>['Card'=>'Card','ABA / KHQR'=>'ABA / KHQR','Cash at Hotel'=>'Cash at Hotel']]]" />
<div class="table-card"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th class="ps-4">Reference</th><th>User</th><th>Payment purpose</th><th>Amount</th><th>Method</th><th>Date</th><th>Status</th><th class="text-end pe-4">Actions</th></tr></thead><tbody>
@forelse($payments as $payment)<tr><td class="ps-4"><strong>{{ $payment->reference_number }}</strong><small class="d-block text-muted">#{{ $payment->id }}</small></td><td>@if($payment->user)<strong>{{ $payment->user->name }}</strong><small class="d-block text-muted">{{ $payment->user->email }}</small>@else User #{{ $payment->user_id }} @endif</td><td>@include('payments._purpose')</td><td class="fw-semibold">${{ number_format($payment->amount,2) }}</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->payment_date?->format('M j, Y') }}</td><td><x-status-badge :status="$payment->status" /></td><td class="text-end pe-4 text-nowrap">
    <a href="{{ route('payments.show',$payment) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
    @if($payment->status === 'Pending')
        <a href="{{ route('payments.edit',$payment) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
        <form action="{{ route('payments.destroy',$payment) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Delete payment {{ $payment->reference_number }}?" type="submit"><i class="bi bi-trash"></i></button></form>
    @elseif($payment->status === 'Paid')
        <form action="{{ route('payments.refund',$payment) }}" method="POST" class="d-inline">@csrf<button class="btn btn-sm btn-outline-warning" data-confirm="Mark payment {{ $payment->reference_number }} as refunded?" type="submit">Mark as Refunded</button></form>
    @endif
</td></tr>
@empty<tr><td colspan="8"><x-list-no-results module="payments" :clear="route('payments.index')" /></td></tr>@endforelse
</tbody></table></div></div>
@if($payments->hasPages())<div class="mt-4">{{ $payments->links('pagination::bootstrap-5') }}</div>@endif
@endsection
