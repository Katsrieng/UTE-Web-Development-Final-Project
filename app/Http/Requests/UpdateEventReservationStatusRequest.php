<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventReservationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(\App\Support\RbacCatalog::routePermission($this->route()?->getName()) ?? '') ?? false;
    }

    public function rules(): array
    {
        return [
            'status_note' => [
                Rule::requiredIf($this->routeIs('management.event-reservations.reject')),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function statusNote(): ?string
    {
        return $this->string('status_note')->trim()->toString() ?: null;
    }
}
