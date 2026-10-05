@extends('layouts.management')
@section('title', 'Edit '.$facility->name)
@section('page-label', 'Facilities')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Guest experience</p><h1>Edit {{ $facility->name }}</h1><p>Update facility details and availability.</p></div><x-status-badge :status="match((string) $facility->status) { '1', 'open' => 'available', 'maintenance' => 'maintenance', default => 'inactive' }" /></div>
<form action="{{ route('management.facilities.update', $facility) }}" method="POST">@csrf @method('PUT') @include('facilities._form')</form>
@endsection
