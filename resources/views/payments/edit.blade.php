@extends('layouts.management')
@section('title', 'Edit Payment')
@section('page-label', 'Payments')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Financial records</p><h1>Edit {{ $payment->reference_number }}</h1><p>Update the payment amount, relationship, method, or status.</p></div><x-status-badge :status="$payment->status" /></div>

@staffroute('payments.update')
<form action="{{ route('payments.update',$payment) }}" method="POST">@csrf @method('PUT') @include('payments._form')</form>
@endstaffroute

@endsection
