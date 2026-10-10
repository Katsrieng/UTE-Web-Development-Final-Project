@extends('layouts.management')
@section('title', 'Edit Membership Type')
@section('page-label', 'Membership Types')
@section('content')
<div class="page-heading">
    <div>
        <p class="section-kicker">Guest loyalty</p>
        <h1>Edit Membership Type</h1>
        <p>Update {{ $membershipType->name }}'s discount, price, and duration.</p>
    </div>
</div>

<form action="{{ route('admin.membership-types.update', $membershipType) }}" method="POST">
    @csrf
    @method('PUT')
    @include('admin.membership_types._form', ['membershipType' => $membershipType])
</form>
@endsection
