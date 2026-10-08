<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\PaymentSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PaymentFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_cannot_access_payments(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('staff.login'));
    }

    public function test_customer_cannot_access_payment_management(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('payments.index'))
            ->assertForbidden();
    }

    public function test_staff_can_access_payment_management(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('payments.index'))
            ->assertOk();
    }

    public function test_staff_can_create_a_valid_event_payment(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking));

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', [
            'user_id' => $customer->id,
            'booking_id' => null,
            'event_booking_id' => $eventBooking->id,
            'reference_number' => 'PAY-FEATURE-001',
        ]);
    }

    public function test_staff_can_create_a_valid_room_payment(): void
    {
        $customer = User::factory()->create();
        $booking = $this->roomBookingFor($customer);
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'booking_id' => $booking->id,
                'event_booking_id' => null,
            ]))
            ->assertRedirect(route('payments.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'user_id' => $customer->id,
            'booking_id' => $booking->id,
            'event_booking_id' => null,
        ]);
    }

    public function test_room_payment_rejects_a_nonexistent_booking(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'booking_id' => 999999,
                'event_booking_id' => null,
            ]))
            ->assertSessionHasErrors('booking_id');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_room_payment_rejects_a_booking_owned_by_another_customer(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $booking = $this->roomBookingFor($otherCustomer);
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'booking_id' => $booking->id,
                'event_booking_id' => null,
            ]))
            ->assertSessionHasErrors('booking_id');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_new_payment_cannot_be_created_as_refunded(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'status' => 'Refunded',
            ]))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_form_does_not_offer_refunded_as_a_status(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('payments.create'))
            ->assertOk()
            ->assertSee('value="Pending"', false)
            ->assertSee('value="Paid"', false)
            ->assertDontSee('value="Refunded"', false);
    }

    public function test_payment_rejects_a_nonexistent_user(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'user_id' => 999999,
            ]));

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_a_nonexistent_event_booking(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'event_booking_id' => 999999,
            ]));

        $response->assertSessionHasErrors('event_booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_an_event_booking_owned_by_another_customer(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($otherCustomer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking));

        $response->assertSessionHasErrors('event_booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_when_neither_booking_type_is_supplied(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'event_booking_id' => null,
            ]));

        $response->assertSessionHasErrors('booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_rejects_when_both_booking_types_are_supplied(): void
    {
        $customer = User::factory()->create();
        $booking = $this->roomBookingFor($customer);
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'booking_id' => $booking->id,
            ]));

        $response->assertSessionHasErrors('booking_id');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_room_booking_id_must_be_positive_when_creating(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        foreach ([0, -1] as $bookingId) {
            $response = $this->actingAs($staff)
                ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                    'booking_id' => $bookingId,
                    'event_booking_id' => null,
                ]));

            $response->assertSessionHasErrors('booking_id');
            $this->assertTrue(collect($response->getSession()->get('errors')->get('booking_id'))
                ->contains(fn (string $message): bool => str_contains($message, 'at least 1')));
        }

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_room_booking_id_must_be_positive_when_updating(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        foreach ([0, -1] as $bookingId) {
            $response = $this->actingAs($staff)
                ->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
                    'booking_id' => $bookingId,
                    'event_booking_id' => null,
                    'status' => 'Pending',
                ]));

            $response->assertSessionHasErrors('booking_id');
            $this->assertTrue(collect($response->getSession()->get('errors')->get('booking_id'))
                ->contains(fn (string $message): bool => str_contains($message, 'at least 1')));
        }

        $this->assertNull($payment->fresh()->booking_id);
        $this->assertSame($eventBooking->id, $payment->fresh()->event_booking_id);
    }

    public function test_update_may_keep_the_existing_reference_number(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        $response = $this->actingAs($staff)
            ->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
                'amount' => 200.00,
                'status' => 'Pending',
            ]));

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame('PAY-FEATURE-001', $payment->fresh()->reference_number);
        $this->assertSame('200.00', $payment->fresh()->amount);
    }

    public function test_update_rejects_a_nonexistent_or_other_customers_room_booking(): void
    {
        $customer = User::factory()->create();
        $otherCustomer = User::factory()->create();
        $otherBooking = $this->roomBookingFor($otherCustomer);
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        foreach ([999999, $otherBooking->id] as $bookingId) {
            $this->actingAs($staff)
                ->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
                    'booking_id' => $bookingId,
                    'event_booking_id' => null,
                    'status' => 'Pending',
                ]))
                ->assertSessionHasErrors('booking_id');
        }

        $this->assertNull($payment->fresh()->booking_id);
        $this->assertSame($eventBooking->id, $payment->fresh()->event_booking_id);
    }

    public function test_reference_number_must_fit_the_database_column_on_create_and_update(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'reference_number' => str_repeat('A', 192),
            ]))
            ->assertSessionHasErrors('reference_number');

        $reference = str_repeat('A', 191);
        $this->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
            'reference_number' => $reference,
            'status' => 'Pending',
        ]))->assertRedirect(route('payments.index'))
            ->assertSessionHasNoErrors();
        $payment = Payment::where('reference_number', $reference)->firstOrFail();

        $this->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
            'reference_number' => str_repeat('B', 192),
            'status' => 'Pending',
        ]))->assertSessionHasErrors('reference_number');

        $this->assertSame($reference, $payment->fresh()->reference_number);
    }

    public function test_amount_rejects_more_than_two_decimal_places_on_create_and_update(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'amount' => '10.001',
            ]))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('payments', 0);

        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        $this->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
            'amount' => '10.001',
            'status' => 'Pending',
        ]))->assertSessionHasErrors('amount');

        $this->assertSame('150.00', $payment->fresh()->amount);
    }

    public function test_amount_stays_within_the_database_range(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
                'amount' => '100000000.00',
            ]))
            ->assertSessionHasErrors('amount');

        $this->post(route('payments.store'), $this->validPayload($customer, $eventBooking, [
            'amount' => '99999999.99',
            'status' => 'Pending',
        ]))->assertRedirect(route('payments.index'))
            ->assertSessionHasNoErrors();

        $payment = Payment::where('reference_number', 'PAY-FEATURE-001')->firstOrFail();
        $this->assertSame('99999999.99', $payment->amount);

        $this->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
            'amount' => '100000000.00',
            'status' => 'Pending',
        ]))->assertSessionHasErrors('amount');

        $this->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
            'amount' => '0.01',
            'status' => 'Pending',
        ]))->assertRedirect(route('payments.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('0.01', $payment->fresh()->amount);
    }

    public function test_pending_payment_can_be_edited(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        $this->actingAs($staff)->get(route('payments.edit', $payment))->assertOk();
        $this->get(route('payments.show', $payment))
            ->assertSeeText('Edit Payment')
            ->assertSeeText('Delete Payment')
            ->assertSeeText('View Receipt');

        $this->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
            'status' => 'Paid',
            'amount' => 175.00,
        ]))->assertRedirect(route('payments.index'));

        $this->assertSame('Paid', $payment->fresh()->status);
        $this->assertSame('175.00', $payment->fresh()->amount);
    }

    public function test_pending_payment_can_be_deleted(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        $this->actingAs($staff)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.index'));

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_paid_payment_cannot_be_edited(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking));

        $this->actingAs($staff)->get(route('payments.edit', $payment))
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('error', 'Only pending payments can be edited.');

        $this->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
            'amount' => 200.00,
        ]))->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('error', 'Only pending payments can be edited.');

        $this->assertSame('150.00', $payment->fresh()->amount);
        $this->get(route('payments.show', $payment))
            ->assertSeeText('Mark as Refunded')
            ->assertDontSeeText('Edit Payment')
            ->assertDontSeeText('Delete Payment')
            ->assertSeeText('View Receipt');
    }

    public function test_paid_payment_cannot_be_deleted(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking));

        $this->actingAs($staff)
            ->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('error', 'Only pending payments can be deleted.');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'Paid']);
    }

    public function test_paid_payment_can_be_refunded_without_changing_other_fields(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking));
        $original = $payment->fresh()->getRawOriginal();

        $this->actingAs($staff)
            ->from(route('payments.show', $payment))
            ->post(route('payments.refund', $payment))
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('success', 'Payment marked as refunded.');

        $refunded = $payment->fresh();
        $this->assertSame('Refunded', $refunded->status);
        foreach (['user_id', 'booking_id', 'event_booking_id', 'amount', 'payment_method', 'payment_date', 'reference_number'] as $field) {
            $this->assertSame($original[$field], $refunded->getRawOriginal($field));
        }
    }

    public function test_pending_payment_cannot_be_refunded(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        $this->actingAs($staff)
            ->from(route('payments.show', $payment))
            ->post(route('payments.refund', $payment))
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('error', 'Only paid payments can be refunded.');

        $this->assertSame('Pending', $payment->fresh()->status);
    }

    public function test_pending_payment_cannot_be_set_to_refunded_through_update(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
        ]));

        // Refunded is no longer a valid value for the update endpoint —
        // it is rejected at the validation layer before reaching the controller.
        $this->actingAs($staff)
            ->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
                'status' => 'Refunded',
            ]))->assertSessionHasErrors(['status']);

        $this->assertSame('Pending', $payment->fresh()->status);
    }

    public function test_refunded_payment_cannot_be_refunded_edited_or_deleted(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $payment = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Refunded',
        ]));

        $this->actingAs($staff)
            ->from(route('payments.show', $payment))
            ->post(route('payments.refund', $payment))
            ->assertSessionHas('error', 'Only paid payments can be refunded.');
        $this->get(route('payments.edit', $payment))
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('error', 'Only pending payments can be edited.');
        $this->put(route('payments.update', $payment), $this->validPayload($customer, $eventBooking, [
            'amount' => 200.00,
        ]))->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('error', 'Only pending payments can be edited.');
        $this->delete(route('payments.destroy', $payment))
            ->assertRedirect(route('payments.show', $payment))
            ->assertSessionHas('error', 'Only pending payments can be deleted.');

        $this->assertSame('Refunded', $payment->fresh()->status);
        $this->assertSame('150.00', $payment->fresh()->amount);
        $this->get(route('payments.show', $payment))
            ->assertSeeText('View Receipt')
            ->assertDontSeeText('Edit Payment')
            ->assertDontSeeText('Delete Payment')
            ->assertDontSeeText('Mark as Refunded');
    }

    public function test_index_shows_actions_appropriate_to_each_payment_status(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $pending = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Pending',
            'reference_number' => 'PAY-ACTIONS-PENDING',
        ]));
        $paid = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Paid',
            'reference_number' => 'PAY-ACTIONS-PAID',
        ]));
        $refunded = Payment::create($this->validPayload($customer, $eventBooking, [
            'status' => 'Refunded',
            'reference_number' => 'PAY-ACTIONS-REFUNDED',
        ]));

        $response = $this->actingAs($staff)->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee(route('payments.edit', $pending));
        $response->assertSee('<form action="'.route('payments.destroy', $pending).'" method="POST"', false);
        $response->assertSee(route('payments.refund', $paid));
        $response->assertDontSee(route('payments.edit', $paid));
        $response->assertDontSee('<form action="'.route('payments.destroy', $paid).'" method="POST"', false);
        $response->assertDontSee(route('payments.edit', $refunded));
        $response->assertDontSee('<form action="'.route('payments.destroy', $refunded).'" method="POST"', false);
        $response->assertDontSee(route('payments.refund', $refunded));
        $response->assertSee(route('payments.show', $refunded));
    }

    public function test_demo_seeder_uses_reserved_reference_without_overwriting_an_existing_payment(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $existingPayment = Payment::create($this->validPayload($customer, $eventBooking, [
            'reference_number' => 'PAY-003',
        ]));

        $this->seed(PaymentSeeder::class);
        $this->seed(PaymentSeeder::class);

        $this->assertSame('150.00', $existingPayment->fresh()->amount);
        $this->assertDatabaseHas('payments', [
            'reference_number' => 'DEMO-EVENT-PAYMENT',
            'user_id' => $customer->id,
            'booking_id' => null,
            'event_booking_id' => $eventBooking->id,
        ]);
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_demo_seeder_does_not_restore_a_refunded_payment_to_paid(): void
    {
        $customer = User::factory()->create();
        EventBooking::factory()->for($customer)->create();

        $this->seed(PaymentSeeder::class);
        $demoPayment = Payment::where('reference_number', 'DEMO-EVENT-PAYMENT')->firstOrFail();
        $demoPayment->update(['status' => 'Refunded']);
        $original = $demoPayment->fresh()->getRawOriginal();

        $this->seed(PaymentSeeder::class);

        $this->assertSame($original, $demoPayment->fresh()->getRawOriginal());
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_receipt_explains_paid_pending_and_refunded_statuses(): void
    {
        $customer = User::factory()->create();
        $eventBooking = EventBooking::factory()->for($customer)->create();
        $staff = User::factory()->staff()->create();
        $messages = [
            'Paid' => 'This payment has been recorded as paid.',
            'Pending' => 'This payment is pending. This document is not proof of completed payment.',
            'Refunded' => 'This payment has been refunded.',
        ];

        foreach ($messages as $status => $message) {
            $payment = Payment::create($this->validPayload($customer, $eventBooking, [
                'status' => $status,
                'reference_number' => 'PAY-RECEIPT-'.strtoupper($status),
            ]));

            $response = $this->actingAs($staff)->get(route('payments.receipt', $payment));

            $response->assertOk();
            $response->assertSeeText($message);
            $response->assertSeeText('Payment amount');
            $response->assertSeeText('Print Receipt');

            if ($status !== 'Paid') {
                $response->assertDontSeeText('This payment has been recorded as paid.');
            }
        }
    }

    public function test_booking_with_attached_payments_cannot_be_deleted(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $booking = $this->roomBookingFor($customer);

        Payment::create([
            'user_id' => $customer->id,
            'booking_id' => $booking->id,
            'amount' => 100.00,
            'payment_method' => 'Cash',
            'payment_date' => '2026-10-01',
            'status' => 'Paid',
            'reference_number' => 'PAY-PREVENT-DEL',
        ]);

        $response = $this->actingAs($staff)
            ->delete(route('bookings.destroy', $booking));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('error', 'Cannot delete Booking #' . $booking->id . ' because it has payment records attached. Please delete or refund the payment records first.');

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
    }

    public function test_booking_without_attached_payments_can_be_deleted(): void
    {
        $customer = User::factory()->create();
        $staff = User::factory()->staff()->create();
        $booking = $this->roomBookingFor($customer);

        $response = $this->actingAs($staff)
            ->delete(route('bookings.destroy', $booking));

        $response->assertRedirect(route('bookings.index'));
        $response->assertSessionHas('success', 'Booking deleted successfully.');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(User $customer, EventBooking $eventBooking, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $customer->id,
            'booking_id' => null,
            'event_booking_id' => $eventBooking->id,
            'amount' => 150.00,
            'payment_method' => 'Card',
            'payment_date' => '2026-10-01',
            'status' => 'Paid',
            'reference_number' => 'PAY-FEATURE-001',
        ], $overrides);
    }

    private function roomBookingFor(User $customer): Booking
    {
        $roomType = RoomType::create([
            'name' => 'Payment test room type',
            'base_price' => 100.00,
            'capacity' => 2,
        ]);
        $room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => 'PAY-101',
            'price_per_night' => 100.00,
        ]);

        return Booking::create([
            'user_id' => $customer->id,
            'room_id' => $room->id,
            'check_in_date' => '2026-10-20',
            'check_out_date' => '2026-10-21',
            'number_of_guests' => 1,
            'total_amount' => 100.00,
            'status' => 'Confirmed',
        ]);
    }
}
