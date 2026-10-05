@extends('layouts.app')

@section('title', 'Create Venue')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h1 class="h3 mb-4">Create Venue</h1>

        <form method="POST" action="{{ route('management.venues.store') }}" enctype="multipart/form-data"
              class="card shadow-sm border-0">
            @csrf
            <div class="card-body p-4">
                @include('management.venues._form')
            </div>
        </form>
    </div>
</div>
@endsection
