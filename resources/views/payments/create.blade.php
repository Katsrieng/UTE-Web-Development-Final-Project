@extends('layouts.management')
@section('title', 'Add Payment')
@section('page-label', 'Payments')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Financial records</p><h1>Add a payment</h1><p>Record a payment against a room or event booking.</p></div></div>

@staffroute('payments.store')
<form action="{{ route('payments.store') }}" method="POST">@csrf @include('payments._form')</form>
@endstaffroute

@endsection
