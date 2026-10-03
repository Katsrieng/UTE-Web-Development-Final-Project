@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>Edit Membership Type</h2>

    <form action="{{ route('admin.membership-types.update', $membershipType) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.membership_types._form', ['membershipType' => $membershipType])
        <button class="btn btn-primary mt-3">Update</button>
        <a href="{{ route('admin.membership-types.index') }}" class="btn btn-link mt-3">Cancel</a>
    </form>
</div>
@endsection
