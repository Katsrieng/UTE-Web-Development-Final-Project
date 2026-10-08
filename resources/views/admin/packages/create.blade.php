@extends('layouts.management')
@section('title', 'New Package')
@section('page-label', 'Packages')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Guest offers</p><h1>New Package</h1><p>Create an optional extra for future hotel bookings.</p></div></div>

@staffroute('admin.packages.store')
<form action="{{ route('admin.packages.store') }}" method="POST">@csrf @include('admin.packages._form')</form>
@endstaffroute

@endsection
