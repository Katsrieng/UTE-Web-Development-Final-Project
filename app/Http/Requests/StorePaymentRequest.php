<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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

            'reference_number' => 'required|string|max:255|unique:payments,reference_number',
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
