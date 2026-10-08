<?php

namespace App\Http\Requests;

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

            'booking_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('bookings', 'id')
                    ->where('user_id', $this->input('user_id')),
            ],
            'event_booking_id' => [
                'nullable',
                'integer',
                Rule::exists('event_bookings', 'id')
                    ->where('user_id', $this->input('user_id')),
            ],

            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',

            'payment_method' => 'required|in:Cash,Card,Bank Transfer',

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

            if (!$bookingId && !$eventBookingId) {
                $validator->errors()->add(
                    'booking_id',
                    'A payment must belong to a room booking or an event booking.'
                );
            }

            if ($bookingId && $eventBookingId) {
                $validator->errors()->add(
                    'booking_id',
                    'A payment cannot belong to both a room booking and an event booking.'
                );
            }
        });
    }
}
