@extends('layouts.management')
@section('title', 'Edit Room '.$room->room_number)
@section('page-label', 'Rooms')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Hotel inventory</p><h1>Edit Room {{ $room->room_number }}</h1><p>Update room details, status, image, and facilities.</p></div><x-status-badge :status="$room->status" /></div>
<form id="room-form" action="{{ route('management.rooms.update', $room) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT') @include('rooms._form')</form>
@include('rooms._gallery-management')
@include('rooms._facilities')
<div class="d-flex justify-content-between mt-4"><a href="{{ route('management.rooms.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" form="room-form" class="btn btn-hotel">Save Changes</button></div>
@endsection
