<?php

namespace App\Http\Requests;

use App\Models\Venue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        $venue = $this->route('venue');

        return $venue instanceof Venue && ($this->user()?->can('update', $venue) ?? false);
    }

    public function rules(): array
    {
        $venue = $this->route('venue');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('venues', 'name')->ignore($venue),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'event_types' => ['required', 'array', 'min:1'],
            'event_types.*' => ['required', 'string', 'distinct', Rule::in(Venue::EVENT_TYPES)],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => [
                'string',
                Rule::in($venue instanceof Venue ? ($venue->images ?? []) : []),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['images', 'images.*', 'remove_images', 'remove_images.*'])) {
                    return;
                }

                $venue = $this->route('venue');

                if (! $venue instanceof Venue) {
                    return;
                }

                $remainingImages = array_diff($venue->images ?? [], $this->input('remove_images', []));
                $newImageCount = count($this->file('images', []));

                if (count($remainingImages) + $newImageCount > 5) {
                    $validator->errors()->add('images', 'A venue can have at most 5 images.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'event_types.required' => 'Select at least one event type.',
            'event_types.min' => 'Select at least one event type.',
            'images.*.max' => 'Each venue image must be 5 MB or smaller.',
        ];
    }
}
