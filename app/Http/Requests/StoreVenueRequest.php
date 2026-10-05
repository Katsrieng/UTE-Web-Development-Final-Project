<?php

namespace App\Http\Requests;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Venue::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:venues,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*' => ['required', 'string', 'distinct', Rule::in(Venue::EVENT_TYPES)],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'event_types.required' => 'Select at least one event type.',
            'event_types.min' => 'Select at least one event type.',
            'images.max' => 'A venue can have at most 5 images.',
            'images.*.max' => 'Each venue image must be 5 MB or smaller.',
        ];
    }
}
