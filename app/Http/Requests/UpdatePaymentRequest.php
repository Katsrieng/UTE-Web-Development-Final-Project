<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',

            'booking_id' => ['nullable', 'integer', 'min:1', Rule::exists('bookings', 'id')->where('user_id', $this->input('user_id'))],
            'membership_purchase_id' => ['nullable', 'integer', Rule::exists('membership_purchases', 'id')->where('user_id', $this->input('user_id'))],
            'transaction_reference' => 'nullable|string|max:191',
            'event_booking_id' => [
                'nullable',
                'integer',
                Rule::exists('event_bookings', 'id')
                    ->where('user_id', $this->input('user_id')),
            ],

            'amount' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',

            'payment_method' => ['required', Rule::in(Payment::METHODS)],

            'payment_date' => 'required|date',

            'status' => 'required|in:Pending,Paid',

            'reference_number' => [
                'required',
                'string',
                'max:191',
                Rule::unique('payments', 'reference_number')
                    ->ignore($this->route('payment')),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $bookingId = $this->booking_id;
            $eventBookingId = $this->event_booking_id;

            if (!$bookingId && !$eventBookingId && !$this->membership_purchase_id) {
                $validator->errors()->add(
                    'booking_id',
                    'A payment must belong to a room booking, event booking or membership purchase.'
                );
            }

            if (count(array_filter([$bookingId, $eventBookingId, $this->membership_purchase_id])) > 1) {
                $validator->errors()->add(
                    'booking_id',
                    'A payment must have exactly one booking or membership purchase.'
                );
            }
        });
    }
}
