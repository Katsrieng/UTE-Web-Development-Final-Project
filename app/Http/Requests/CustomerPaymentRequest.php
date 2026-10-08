<?php

namespace App\Http\Requests;

use App\Models\Payment;
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
        return ['payment_method' => ['required', Rule::in(Payment::CUSTOMER_METHODS)],
            'transaction_reference' => ['nullable', 'required_if:payment_method,ABA / KHQR', 'string', 'max:191']];
    }
}
