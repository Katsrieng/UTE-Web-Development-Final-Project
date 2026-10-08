@extends('layouts.management')
@section('title', 'Edit '.$roomType->name)
@section('page-label', 'Room Types')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Room catalogue</p><h1>Edit {{ $roomType->name }}</h1><p>Update category details used across its rooms.</p></div></div>

@staffroute('management.room-types.update')
<form action="{{ route('management.room-types.update', $roomType) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT') @include('room_types._form')</form>
@endstaffroute

@endsection
