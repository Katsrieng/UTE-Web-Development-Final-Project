@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2>New Membership Type</h2>

    <form action="{{ route('admin.membership-types.store') }}" method="POST">
        @csrf
        @include('admin.membership_types._form')
        <button class="btn btn-primary mt-3">Create</button>
        <a href="{{ route('admin.membership-types.index') }}" class="btn btn-link mt-3">Cancel</a>
    </form>
</div>
@endsection
