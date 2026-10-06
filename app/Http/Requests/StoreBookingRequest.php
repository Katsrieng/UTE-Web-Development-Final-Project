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
            $packages = $this->input('packages');
            if (is_array($packages)) {
                foreach ($packages as &$package) {
                    if (is_array($package) && isset($package['package_id']) && ! array_key_exists('quantity', $package)) {
                        $package['quantity'] = 1;
                    }
                }
                unset($package);
                $this->merge(['packages' => $packages]);
            }
        }
    }

    public function rules(): array
    {
        $rules = [
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
        if ($this->routeIs('customer.bookings.*')) {
            $rules += [
                'packages' => 'nullable|array',
                'packages.*' => 'array',
                'packages.*.package_id' => ['required', 'integer', 'distinct', Rule::exists('packages', 'id')->where('status', 'active')],
                'packages.*.quantity' => 'required|integer|min:1',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'Please select an active customer for this booking.',
            'packages.*.package_id.exists' => 'This add-on is no longer available. Please review your selection.',
            'packages.*.package_id.distinct' => 'Select each add-on only once.',
            'packages.*.quantity.min' => 'Choose at least 1 unit for a selected add-on.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->routeIs('customer.bookings.*')) {
            return route('customer.bookings.create', $this->route('room'));
        }

        return parent::getRedirectUrl();
    }
}
