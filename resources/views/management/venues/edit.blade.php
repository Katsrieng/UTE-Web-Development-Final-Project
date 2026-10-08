@extends('layouts.management')

@section('title', 'Edit '.$venue->name)
@section('page-label', 'Venues')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h1 class="h3 mb-4">Edit {{ $venue->name }}</h1>


@staffroute('management.venues.update')
<form method="POST" action="{{ route('management.venues.update', $venue) }}" enctype="multipart/form-data"
              class="card shadow-sm border-0">
            @csrf
            @method('PUT')
            <div class="card-body p-4">
                @include('management.venues._form')
            </div>
        </form>
@endstaffroute

    </div>
</div>
@endsection
