@extends('layouts.management')
@section('title', 'New Membership Type')
@section('page-label', 'Membership Types')
@section('content')
<div class="page-heading">
    <div>
        <p class="section-kicker">Guest loyalty</p>
        <h1>New Membership Type</h1>
        <p>Add a new discount tier for hotel customers.</p>
    </div>
</div>

<form action="{{ route('admin.membership-types.store') }}" method="POST">
    @csrf
    @include('admin.membership_types._form')
</form>
@endsection
