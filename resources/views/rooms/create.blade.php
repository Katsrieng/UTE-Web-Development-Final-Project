@extends('layouts.management')
@section('title', 'Add Room')
@section('page-label', 'Rooms')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Hotel inventory</p><h1>Add a room</h1><p>Create a room record, set its rate, and assign facilities.</p></div></div>
<form id="room-form" action="{{ route('management.rooms.store') }}" method="POST" enctype="multipart/form-data">@csrf @include('rooms._form')</form>
@include('rooms._gallery-management')
@include('rooms._facilities')
<div class="d-flex justify-content-between mt-4"><a href="{{ route('management.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" form="room-form" class="btn btn-hotel">Create Room</button></div>
@endsection
