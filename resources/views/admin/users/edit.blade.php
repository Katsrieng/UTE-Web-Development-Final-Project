@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="card shadow-sm">
            <div class="card-header">Edit User: {{ $user->name }}</div>
            <div class="card-body">

                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    @include('admin.users._form')

                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
