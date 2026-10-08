@extends('layouts.management')
@section('title', 'New Membership Type')
@section('page-label', 'Membership Types')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Guest loyalty</p><h1>New Membership Type</h1><p>Define a loyalty tier, discount, and active period.</p></div></div>

@staffroute('admin.membership-types.store')
<form action="{{ route('admin.membership-types.store') }}" method="POST">@csrf @include('admin.membership_types._form')</form>
@endstaffroute

@endsection
