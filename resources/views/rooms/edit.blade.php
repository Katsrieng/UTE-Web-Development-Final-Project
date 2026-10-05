@extends('layouts.management')
@section('title', 'Edit Room '.$room->room_number)
@section('page-label', 'Rooms')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Hotel inventory</p><h1>Edit Room {{ $room->room_number }}</h1><p>Update room details, status, image, and facilities.</p></div><x-status-badge :status="$room->status" /></div>
<form action="{{ route('management.rooms.update', $room) }}" method="POST" enctype="multipart/form-data">@csrf @method('PUT') @include('rooms._form')</form>
@endsection
