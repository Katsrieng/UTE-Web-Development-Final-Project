<?php

namespace App\Http\Requests;

use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Services\BookingPaymentSlipService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($booking = $this->route('booking')) { abort_unless($booking->user_id === $this->user()?->id, 404); }
        return $this->user()?->isCustomer() && $this->user()?->is_active;
    }

    protected function prepareForValidation(): void
    {
        // Card inputs have no names in the UI. Also discard forged sensitive fields before error flashing.
        $this->replace($this->only(['payment_method', 'transaction_reference', '_token']));
        // Laravel's exception handler flashes the original HTTP request, not this FormRequest copy.
        $this->container->make('request')->replace($this->all());
    }

    public function rules(): array
    {
        return ['payment_method' => ['required', Rule::in(PaymentSetting::current()->khqrAvailable() ? Payment::CUSTOMER_METHODS : ['Card', 'Cash at Hotel'])],
            'transaction_reference' => ['nullable', Rule::requiredIf(! $this->route('booking') && $this->input('payment_method') === 'ABA / KHQR'), 'string', 'max:191'],
            'payment_slip' => $this->route('booking') && $this->input('payment_method') === 'ABA / KHQR'
                ? BookingPaymentSlipService::rules() : ['exclude']];
    }
}
