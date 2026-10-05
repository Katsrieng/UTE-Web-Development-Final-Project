@extends('layouts.management')
@section('title', 'Add Room Type')
@section('page-label', 'Room Types')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Room catalogue</p><h1>Add a room type</h1><p>Define the standard capacity, bed, and starting price.</p></div></div>
<form action="{{ route('management.room-types.store') }}" method="POST" enctype="multipart/form-data">@csrf @include('room_types._form')</form>
@endsection
