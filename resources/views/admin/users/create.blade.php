@extends('layouts.management')

@section('title', 'Add Staff')
@section('page-label', 'Staff Accounts')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="card shadow-sm">
            <div class="card-header">Add Staff</div>
            <div class="card-body">
                <p class="text-muted small">Staff accounts are created by administrators and use the Staff Login portal.</p>


@staffroute('admin.users.store')
<form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    @include('admin.users._form')

                    <button type="submit" class="btn btn-hotel">Create Account</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
@endstaffroute


            </div>
        </div>

    </div>
</div>
@endsection
