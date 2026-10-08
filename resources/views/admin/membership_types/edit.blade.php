@extends('layouts.management')
@section('title', 'Edit '.$membershipType->name)
@section('page-label', 'Membership Types')
@section('content')
<div class="page-heading"><div><p class="section-kicker">Guest loyalty</p><h1>Edit {{ $membershipType->name }}</h1><p>Update this membership tier and its benefits.</p></div><x-status-badge :status="$membershipType->status" /></div>

@staffroute('admin.membership-types.update')
<form action="{{ route('admin.membership-types.update',$membershipType) }}" method="POST">@csrf @method('PUT') @include('admin.membership_types._form')</form>
@endstaffroute

@endsection
