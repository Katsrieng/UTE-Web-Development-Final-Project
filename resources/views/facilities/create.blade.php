@extends('layouts.management')
@section('title', 'Add Facility')
@section('page-label', 'Facilities')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Guest experience</p><h1>Add a facility</h1><p>Create an amenity that can be assigned to hotel rooms.</p></div></div>
<form action="{{ route('management.facilities.store') }}" method="POST">@csrf @include('facilities._form')</form>
@endsection
