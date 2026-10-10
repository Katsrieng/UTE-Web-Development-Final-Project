@extends('layouts.management')
@section('title', 'New Package')
@section('page-label', 'Packages')
@section('content')
<div class="page-heading">
    <div>
        <p class="section-kicker">Add-ons</p>
        <h1>New Package</h1>
        <p>Add a new booking add-on package.</p>
    </div>
</div>

<form action="{{ route('admin.packages.store') }}" method="POST">
    @csrf
    @include('admin.packages._form')
</form>
@endsection
