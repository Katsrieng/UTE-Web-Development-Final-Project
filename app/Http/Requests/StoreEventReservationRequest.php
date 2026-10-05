<?php

namespace App\Http\Requests;

use App\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEventReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isCustomer() ?? false;
    }

    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'event_type' => ['required', 'string', Rule::in(Venue::EVENT_TYPES)],
            'event_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:100000'],
            'special_requests' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny([
                    'venue_id',
                    'event_type',
                    'event_date',
                    'start_time',
                    'end_time',
                    'guest_count',
                ])) {
                    return;
                }

                $venue = Venue::find($this->integer('venue_id'));

                if (! $venue?->is_active) {
                    $validator->errors()->add('venue_id', 'The selected venue is not available for reservations.');

                    return;
                }

                if (! $venue->supportsEventType($this->string('event_type')->toString())) {
                    $validator->errors()->add('event_type', 'The selected venue does not support this event type.');
                }

                if ($this->integer('guest_count') > $venue->capacity) {
                    $validator->errors()->add(
                        'guest_count',
                        "The guest count may not exceed this venue's capacity of {$venue->capacity}.",
                    );
                }

                if ($this->endsAt()->lessThanOrEqualTo($this->startsAt())) {
                    $validator->errors()->add('end_time', 'The end time must be after the start time.');
                }

                if (! $this->startsAt()->isFuture()) {
                    $validator->errors()->add('start_time', 'The event start time must be in the future.');
                }
            },
        ];
    }

    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            '!Y-m-d H:i',
            $this->string('event_date').' '.$this->string('start_time'),
            config('app.timezone'),
        );
    }

    public function endsAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat(
            '!Y-m-d H:i',
            $this->string('event_date').' '.$this->string('end_time'),
            config('app.timezone'),
        );
    }

    /**
     * @return array{event_type: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, guest_count: int, special_requests: ?string}
     */
    public function bookingData(): array
    {
        return [
            'event_type' => $this->string('event_type')->toString(),
            'starts_at' => $this->startsAt(),
            'ends_at' => $this->endsAt(),
            'guest_count' => $this->integer('guest_count'),
            'special_requests' => $this->string('special_requests')->trim()->toString() ?: null,
        ];
    }

    public function messages(): array
    {
        return [
            'event_date.after_or_equal' => 'The event date must be today or later.',
            'event_type.in' => 'Select a valid event type.',
        ];
    }
}
