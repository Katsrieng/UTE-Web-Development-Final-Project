<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('customer.bookings.*')) {
            // Customer and room identity are determined by the server, never form inputs.
            $this->merge([
                'user_id' => $this->user()->id,
                'room_id' => $this->route('room')->id,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_CUSTOMER)->where('is_active', true),
            ],
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'number_of_guests' => 'required|integer|min:1',
            'special_request' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return ['user_id.exists' => 'Please select an active customer for this booking.'];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->routeIs('customer.bookings.*')) {
            return route('customer.bookings.create', $this->route('room'));
        }

        return parent::getRedirectUrl();
    }
}
