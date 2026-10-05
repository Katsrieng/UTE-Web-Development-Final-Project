@extends('layouts.app')
@section('title', 'Reserve Room '.$room->room_number)
@section('content')
<div class="breadcrumb-bar"><div class="container"><a href="{{ route('rooms.show', $room) }}"><i class="bi bi-arrow-left me-1"></i> Room details</a></div></div>
<section class="content-section compact">
    <div class="container">
        <div class="page-heading">
            <div><p class="section-kicker">Plan your stay</p><h1>Reserve Room {{ $room->room_number }}</h1><p>Choose your dates and guests to check availability.</p></div>
            <a href="{{ route('customer.bookings.index') }}" class="btn btn-outline-secondary">My Bookings</a>
        </div>
        @if($errors->any())
            <div class="validation-summary mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                <div><strong>Please correct the following:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            </div>
        @endif
        <div class="row g-4 align-items-start">
            <div class="col-lg-8">
                <form method="POST" action="{{ route('customer.bookings.store', $room) }}" id="reservationForm" class="card shadow-sm border-0">
                    @csrf
                    <div class="card-body p-4 p-lg-5">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="check_in_date" class="form-label">Check-in date</label>
                                <input id="check_in_date" type="date" name="check_in_date" min="{{ now()->toDateString() }}" value="{{ old('check_in_date', $reservation['check_in_date'] ?? '') }}" class="form-control @error('check_in_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-md-6">
                                <label for="check_out_date" class="form-label">Check-out date</label>
                                <input id="check_out_date" type="date" name="check_out_date" min="{{ now()->toDateString() }}" value="{{ old('check_out_date', $reservation['check_out_date'] ?? '') }}" class="form-control @error('check_out_date') is-invalid @enderror" required>
                            </div>
                            <div class="col-md-6">
                                <label for="number_of_guests" class="form-label">Number of guests</label>
                                <input id="number_of_guests" type="number" name="number_of_guests" min="1" max="{{ $room->roomType->capacity }}" value="{{ old('number_of_guests', $reservation['number_of_guests'] ?? 1) }}" class="form-control @error('number_of_guests') is-invalid @enderror" required>
                                <div class="form-text">Up to {{ $room->roomType->capacity }} guests.</div>
                            </div>
                            <div class="col-12">
                                <label for="special_request" class="form-label">Special request <span class="text-muted">(optional)</span></label>
                                <textarea id="special_request" name="special_request" rows="4" class="form-control @error('special_request') is-invalid @enderror">{{ old('special_request', $reservation['special_request'] ?? '') }}</textarea>
                            </div>
                        </div>
                        @isset($totalAmount)
                            <div id="availabilityResult" class="alert alert-success mt-4 mb-0" role="status">
                                <strong class="d-block">Available for your selected dates</strong>
                                <span>{{ $numberOfNights }} nights · {{ $reservation['number_of_guests'] }} guests · ${{ number_format($totalAmount, 2) }} total</span>
                                <p class="small mb-0 mt-2">Your booking will start as Pending, awaiting hotel confirmation.</p>
                            </div>
                        @endisset
                    </div>
                    <div class="card-footer bg-white p-4 d-flex flex-wrap gap-2">
                        <button type="submit" formaction="{{ route('customer.bookings.availability', $room) }}" class="btn btn-hotel-outline">Check Availability</button>
                        @isset($totalAmount)<button type="submit" id="bookRoomButton" class="btn btn-hotel">Book Room</button>@endisset
                    </div>
                </form>
            </div>
            <div class="col-lg-4">
                <aside class="detail-panel">
                    <p class="section-kicker">{{ $room->roomType->name }}</p><h2 class="h3">Room {{ $room->room_number }}</h2>
                    <dl class="detail-list"><div><dt>Nightly rate</dt><dd>${{ number_format($room->price_per_night, 2) }}</dd></div><div><dt>Capacity</dt><dd>{{ $room->roomType->capacity }} guests</dd></div></dl>
                    <p class="text-muted small mb-0">Your reservation is created when you choose Book Room. Availability and the total are checked again at that time.</p>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection
@isset($totalAmount)
    @push('scripts')
        <script>
            ['check_in_date', 'check_out_date', 'number_of_guests'].forEach(function (id) {
                document.getElementById(id).addEventListener('input', function () {
                    document.getElementById('availabilityResult').hidden = true;
                    document.getElementById('bookRoomButton').hidden = true;
                });
            });
        </script>
    @endpush
@endisset
