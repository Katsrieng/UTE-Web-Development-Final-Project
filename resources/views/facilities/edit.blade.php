@extends('layouts.management')
@section('title', 'Edit '.$facility->name)
@section('page-label', 'Facilities')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Guest experience</p><h1>Edit {{ $facility->name }}</h1><p>Update facility details and availability.</p></div><x-status-badge :status="$facility->status" /></div>

@staffroute('management.facilities.update')
<form action="{{ route('management.facilities.update', $facility) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT') @include('facilities._form')</form>
@endstaffroute

@endsection
