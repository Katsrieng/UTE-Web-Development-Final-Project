@extends('layouts.management')
@section('title', 'Add Room')
@section('page-label', 'Rooms')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Hotel inventory</p><h1>Add a room</h1><p>Create a room record, set its rate, and assign facilities.</p></div></div>
<form action="{{ route('management.rooms.store') }}" method="POST" enctype="multipart/form-data">@csrf @include('rooms._form')</form>
@endsection
