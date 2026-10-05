@extends('layouts.management')

@section('title', 'Add User')
@section('page-label', 'Users')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="card shadow-sm">
            <div class="card-header">Add User</div>
            <div class="card-body">

                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    @include('admin.users._form')

                    <button type="submit" class="btn btn-primary">Create user</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
