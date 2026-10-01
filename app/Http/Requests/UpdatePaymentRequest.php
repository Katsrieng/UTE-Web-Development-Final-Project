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
            'user_id' => 'required|integer',

            'booking_id' => 'nullable|integer',
            'event_booking_id' => 'nullable|integer',

            'amount' => 'required|numeric|min:0.01',

            'payment_method' => 'required|in:Cash,Card,Bank Transfer',

            'payment_date' => 'required|date',

            'status' => 'required|in:Pending,Paid,Refunded',

            'reference_number' => [
                'required',
                'string',
                'max:255',
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
