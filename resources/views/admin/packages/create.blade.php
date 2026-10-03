@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>New Package</h2>

    <form action="{{ route('admin.packages.store') }}" method="POST">
        @csrf
        @include('admin.packages._form')
        <button class="btn btn-primary mt-3">Create</button>
        <a href="{{ route('admin.packages.index') }}" class="btn btn-link mt-3">Cancel</a>
    </form>
</div>
@endsection
