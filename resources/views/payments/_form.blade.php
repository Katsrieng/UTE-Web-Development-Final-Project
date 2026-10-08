@php($editing = isset($payment))
@if($errors->any())
    <div class="validation-summary mb-4">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

@if(isset($payment) && ($payment->booking_id || $payment->membership_purchase_id))
<div class="card shadow-sm border-0"><div class="card-body p-4">
    @include('payments._purpose')
    <p class="small text-muted">Customer, payment purpose and amount are linked to the original record.</p>
    @foreach(['user_id','booking_id','event_booking_id','membership_purchase_id'] as $field)<input type="hidden" name="{{ $field }}" value="{{ $payment->$field }}">@endforeach
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="amount">Amount</label><input id="amount" class="form-control" name="amount" value="{{ $payment->booking_id ? $payment->booking->total_amount : $payment->membershipPurchase->price }}" readonly></div>
        <div class="col-md-6"><label class="form-label" for="reference_number">Payment reference</label><input id="reference_number" class="form-control" name="reference_number" value="{{ $payment->reference_number }}" maxlength="191" required></div>
        <div class="col-md-6"><label class="form-label" for="payment_method">Method</label><select id="payment_method" name="payment_method" class="form-select">@foreach(App\Models\Payment::METHODS as $method)<option @selected($payment->payment_method === $method)>{{ $method }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="payment_date">Date</label><input id="payment_date" type="date" class="form-control" name="payment_date" value="{{ $payment->payment_date->toDateString() }}" required></div>
        <div class="col-md-6"><label class="form-label" for="status">Status</label><select id="status" name="status" class="form-select"><option>Pending</option>@can('verify_payments')<option>Paid</option>@endcan</select></div>
        <div class="col-md-6"><label class="form-label" for="transaction_reference">Transaction reference</label><input id="transaction_reference" name="transaction_reference" class="form-control" value="{{ $payment->transaction_reference }}" maxlength="191"></div>
    </div>
</div></div>
@else
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-4">Payment information</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="user_id" class="form-label">Customer</label>
                        <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">Select customer</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) old('user_id', $payment->user_id ?? request('user_id', '')) === (string) $user->id)>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="reference_number" class="form-label">Reference number</label>
                        <input id="reference_number" type="text" name="reference_number"
                            value="{{ old('reference_number', $payment->reference_number ?? '') }}"
                            class="form-control @error('reference_number') is-invalid @enderror"
                            placeholder="PAY-001" required>
                        @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input id="amount" type="number" min="0.01" max="99999999.99" step="0.01"
                                name="amount" value="{{ old('amount', $payment->amount ?? request('amount', '')) }}"
                                class="form-control @error('amount') is-invalid @enderror" required>
                        </div>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="payment_date" class="form-label">Payment date</label>
                        <input id="payment_date" type="date" name="payment_date"
                            value="{{ old('payment_date', isset($payment) ? $payment->payment_date?->format('Y-m-d') : request('payment_date', today()->format('Y-m-d'))) }}"
                            class="form-control @error('payment_date') is-invalid @enderror" required>
                        @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="payment_method" class="form-label">Payment method</label>
                        <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                            <option value="">Select method</option>
                            @foreach(App\Models\Payment::METHODS as $method)
                                <option value="{{ $method }}" @selected(old('payment_method', $payment->payment_method ?? request('payment_method', '')) === $method)>
                                    {{ $method }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach(['Pending', 'Paid'] as $status)
                                @if($status === 'Paid' && !auth()->user()->hasPermission('verify_payments')) @continue @endif
                                <option value="{{ $status }}" @selected(old('status', $payment->status ?? 'Pending') === $status)>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-2">Related booking</h2>
                <p class="text-muted small mb-4">Choose either a room booking or event booking—not both.</p>

                <div class="mb-3">
                    <label for="booking_id" class="form-label">Room booking</label>
                    <select id="booking_id" name="booking_id" class="form-select @error('booking_id') is-invalid @enderror">
                        <option value="">No room booking</option>
                        @foreach($roomBookings as $roomBooking)
                            <option value="{{ $roomBooking->id }}" @selected((string) old('booking_id', $payment->booking_id ?? request('booking_id', '')) === (string) $roomBooking->id)>
                                #{{ $roomBooking->id }}
                                — {{ $roomBooking->user?->name ?? 'Unknown customer' }}
                                — Room {{ $roomBooking->room?->room_number ?? '?' }}
                                — {{ $roomBooking->check_in_date ?? 'Date unavailable' }}
                                ({{ $roomBooking->status }})
                            </option>
                        @endforeach
                    </select>
                    @error('booking_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Leave empty for event payments.</div>
                </div>

                <div>
                    <label for="event_booking_id" class="form-label">Event booking</label>
                    <select id="event_booking_id" name="event_booking_id" class="form-select @error('event_booking_id') is-invalid @enderror">
                        <option value="">No event booking</option>
                        @foreach($eventBookings as $eventBooking)
                            <option value="{{ $eventBooking->id }}" @selected((string) old('event_booking_id', $payment->event_booking_id ?? '') === (string) $eventBooking->id)>
                                #{{ $eventBooking->id }}
                                — {{ $eventBooking->user?->name ?? 'Unknown customer' }}
                                — {{ $eventBooking->venue?->name ?? 'Unknown venue' }}
                                — {{ $eventBooking->starts_at?->format('M j, Y') ?? 'Date unavailable' }}
                            </option>
                        @endforeach
                    </select>
                    @error('event_booking_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Leave empty for room payments.</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endif
<div class="d-flex justify-content-between mt-4">

@staffroute('payments.index')
<a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Cancel</a>
@endstaffroute

    <button class="btn btn-hotel" type="submit">{{ $editing ? 'Save Changes' : 'Create Payment' }}</button>
</div>
