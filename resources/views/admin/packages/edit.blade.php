@extends('layouts.management')
@section('title', 'Edit '.$package->name)
@section('page-label', 'Packages')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Guest offers</p><h1>Edit {{ $package->name }}</h1><p>Update package details, pricing, or availability.</p></div><x-status-badge :status="$package->status" /></div>

@staffroute('admin.packages.update')
<form action="{{ route('admin.packages.update',$package) }}" method="POST">@csrf @method('PUT') @include('admin.packages._form')</form>
@endstaffroute

@endsection
