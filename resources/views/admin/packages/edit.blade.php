@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>Edit Package</h2>

    <form action="{{ route('admin.packages.update', $package) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.packages._form', ['package' => $package])
        <button class="btn btn-primary mt-3">Update</button>
        <a href="{{ route('admin.packages.index') }}" class="btn btn-link mt-3">Cancel</a>
    </form>
</div>
@endsection
