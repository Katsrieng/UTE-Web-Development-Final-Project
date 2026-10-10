@extends('layouts.management')
@section('title', 'Edit Package')
@section('page-label', 'Packages')
@section('content')
<div class="page-heading">
    <div>
        <p class="section-kicker">Add-ons</p>
        <h1>Edit Package</h1>
        <p>Update {{ $package->name }}'s price, type, and description.</p>
    </div>
</div>

<form action="{{ route('admin.packages.update', $package) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.packages._form', ['package' => $package])
</form>
@endsection
