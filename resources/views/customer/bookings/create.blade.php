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
        <form method="POST" action="{{ route('customer.bookings.store', $room) }}" id="reservationForm" class="reservation-checkout">
            @csrf
            <div class="row g-4">
                <div class="col-lg-8">
                    <section class="checkout-inputs" aria-labelledby="stay-details-heading">
                        <h2 id="stay-details-heading" class="h5 mb-3">Your stay</h2>
                        <div class="row g-3">
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
                                <textarea id="special_request" name="special_request" rows="3" class="form-control @error('special_request') is-invalid @enderror">{{ old('special_request', $reservation['special_request'] ?? '') }}</textarea>
                            </div>
                        </div>
                        @if(isset($totalAmount) || old('packages'))@include('customer.bookings._packages')@endif
                    </section>
                </div>
                <div class="col-lg-4">
                    <aside id="bookingSummary" class="checkout-summary" aria-labelledby="booking-summary-heading">
                        <h2 id="booking-summary-heading" class="h5 mb-3">Your Booking</h2>
                        <p class="fw-semibold mb-1">Room {{ $room->room_number }} · {{ $room->roomType->name }}</p>
                        <p class="small text-muted mb-3">${{ number_format($room->price_per_night, 2) }} / night · Up to {{ $room->roomType->capacity }} guests</p>
                        @isset($totalAmount)
                            <div id="availabilityResult" class="checkout-availability" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Available for your selected dates</div>
                            <div id="previewStay" class="small mt-2 mb-3"><p class="mb-1">{{ Carbon\Carbon::parse($reservation['check_in_date'])->format('M j') }} → {{ Carbon\Carbon::parse($reservation['check_out_date'])->format('M j, Y') }}</p><span class="text-muted">{{ $numberOfNights }} nights · {{ $reservation['number_of_guests'] }} guests</span></div>
                            <div id="bookingPricePreview" class="checkout-prices" data-room-total-cents="{{ (int) round($roomTotal * 100) }}" data-discount-basis-points="{{ (int) round((float) $membershipPricing['membership_discount_percentage'] * 100) }}">
                                <div class="checkout-price-line"><span>Room stay</span><span>${{ number_format($roomTotal, 2) }}</span></div>
                                <div id="livePackageLines">@foreach($packageLines as $line)<div class="checkout-price-line"><span>{{ $line['name'] }} × {{ $line['quantity'] }}</span><span>${{ number_format($line['line_total'], 2) }}</span></div>@endforeach</div>
                                <div id="livePackageTotalRow" @if(!$packageLines) hidden @endif><div class="checkout-price-line small text-muted"><span>Package total</span><span id="livePackageTotal">${{ number_format($packageTotal, 2) }}</span></div></div>
                                @if((float) $membershipPricing['membership_discount_percentage'] > 0)
                                    <div class="checkout-price-line mt-3 pt-2 border-top"><span>Subtotal</span><span id="liveBookingSubtotal">${{ number_format($subtotal, 2) }}</span></div>
                                    <div class="checkout-price-line"><span>{{ $membershipPricing['membership_name'] }} Member · {{ number_format($membershipPricing['membership_discount_percentage'], 2) }}% discount</span><span id="liveMembershipDiscount" class="text-success">−${{ number_format($membershipPricing['membership_discount_amount'], 2) }}</span></div>
                                    <p class="small text-success mb-2">You save <span id="liveMembershipSaving">${{ number_format($membershipPricing['membership_discount_amount'], 2) }}</span></p>
                                @endif
                                <div class="checkout-total"><span>Total</span><strong id="liveBookingTotal">${{ number_format($totalAmount, 2) }}</strong></div>
                            </div>
                            <p id="staleTotalNotice" class="checkout-stale small" role="status" hidden>Your dates or guests changed. Check availability again before booking.</p>
                            <button type="submit" id="bookRoomButton" class="btn btn-hotel w-100 mt-3">Book Room</button>
                        @else
                            <p class="small text-muted py-3 border-top">Choose your dates and guests, then check availability to review your total.</p>
                        @endisset
                        <button id="checkAvailabilityButton" @isset($totalAmount) hidden @endisset type="submit" formaction="{{ route('customer.bookings.availability', $room) }}" class="btn {{ isset($totalAmount) ? 'btn-outline-secondary' : 'btn-hotel' }} w-100 mt-2">Check Availability</button>
                        @isset($totalAmount)<p class="checkout-confirmation small text-muted mt-3 mb-0">Your booking starts as Pending, awaiting hotel confirmation.</p>@endisset
                    </aside>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection
@push('scripts')
<script>
    const pricePreview = document.getElementById('bookingPricePreview');
    const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    function invalidateBookingPreview() {
        ['availabilityResult', 'bookRoomButton', 'bookingPricePreview', 'previewStay'].forEach(function (id) {
            const element = document.getElementById(id);
            if (element) element.hidden = true;
        });
        const bookButton = document.getElementById('bookRoomButton');
        if (bookButton) bookButton.disabled = true;
        const notice = document.getElementById('staleTotalNotice');
        if (notice) notice.hidden = false;
        document.getElementById('checkAvailabilityButton').hidden = false;
        syncQuantityLimits();
    }
    function syncQuantityLimits() {
        const guests = Math.max(1, Number(document.getElementById('number_of_guests').value) || 1);
        document.querySelectorAll('[data-package-selection]').forEach(function (checkbox) {
            const quantity = document.getElementById(checkbox.dataset.quantityTarget);
            if (checkbox.dataset.packageType === 'buffet') {
                quantity.max = guests;
                if (Number(quantity.value) > guests) quantity.value = guests;
            }
            const wrap = quantity.closest('[data-quantity-wrap]');
            if (wrap) wrap.querySelectorAll('[data-quantity-step]').forEach(function (button) {
                button.disabled = quantity.disabled || (Number(button.dataset.quantityStep) < 0 ? Number(quantity.value) <= 1 : Number(quantity.value) >= Number(quantity.max));
            });
        });
    }
    function updatePackageTotals() {
        syncQuantityLimits();
        if (!pricePreview) return;
        const invalidQuantity = Array.from(document.querySelectorAll('[data-package-quantity]')).some(function (quantity) {
            return !quantity.disabled && (!quantity.validity.valid || quantity.value === '');
        });
        document.getElementById('bookRoomButton').disabled = pricePreview.hidden || invalidQuantity;
        if (invalidQuantity) return;
        const lines = document.getElementById('livePackageLines');
        lines.replaceChildren();
        let packageCents = 0;
        document.querySelectorAll('[data-package-selection]').forEach(function (checkbox) {
            if (!checkbox.checked) return;
            const quantity = document.getElementById(checkbox.dataset.quantityTarget);
            if (!quantity.validity.valid || quantity.value === '') return;
            const lineCents = Number(checkbox.dataset.unitCents) * Number(quantity.value);
            packageCents += lineCents;
            const line = document.createElement('div');
            line.className = 'checkout-price-line';
            const label = document.createElement('span');
            label.textContent = checkbox.dataset.packageName + ' × ' + quantity.value;
            const amount = document.createElement('span');
            amount.textContent = money.format(lineCents / 100);
            line.append(label, amount);
            lines.append(line);
        });
        document.getElementById('livePackageTotalRow').hidden = packageCents === 0;
        document.getElementById('livePackageTotal').textContent = money.format(packageCents / 100);
        const subtotalCents = Number(pricePreview.dataset.roomTotalCents) + packageCents;
        const discountCents = Math.floor((subtotalCents * Number(pricePreview.dataset.discountBasisPoints || 0) + 5000) / 10000);
        const subtotal = document.getElementById('liveBookingSubtotal');
        if (subtotal) {
            subtotal.textContent = money.format(subtotalCents / 100);
            document.getElementById('liveMembershipDiscount').textContent = '−' + money.format(discountCents / 100);
            document.getElementById('liveMembershipSaving').textContent = money.format(discountCents / 100);
        }
        document.getElementById('liveBookingTotal').textContent = money.format((subtotalCents - discountCents) / 100);
    }
    ['check_in_date', 'check_out_date', 'number_of_guests'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', invalidateBookingPreview);
        document.getElementById(id).addEventListener('change', invalidateBookingPreview);
    });
    document.querySelectorAll('[data-package-selection]').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const quantity = document.getElementById(checkbox.dataset.quantityTarget);
            quantity.disabled = !checkbox.checked;
            const wrap = quantity.closest('[data-quantity-wrap]');
            if (wrap) wrap.hidden = !checkbox.checked;
            updatePackageTotals();
        });
    });
    document.querySelectorAll('[data-package-quantity]').forEach(function (quantity) {
        quantity.addEventListener('input', updatePackageTotals);
    });
    document.querySelectorAll('[data-quantity-step]').forEach(function (button) {
        button.addEventListener('click', function () {
            const quantity = document.getElementById(button.dataset.quantityTarget);
            if (quantity.disabled) return;
            quantity.value = Math.min(Number(quantity.max), Math.max(1, (Number(quantity.value) || 1) + Number(button.dataset.quantityStep)));
            quantity.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
    syncQuantityLimits();
</script>
@endpush
