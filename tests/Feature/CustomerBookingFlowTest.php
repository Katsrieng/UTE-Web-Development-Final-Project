<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CustomerBookingFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $customer;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-06 09:00:00');
        $this->customer = User::factory()->create();
        $this->room = $this->makeRoom();
        $this->actingAs($this->customer);
    }

    public function test_customer_creates_a_pending_booking_using_server_identity_and_price(): void
    {
        $otherCustomer = User::factory()->create();
        $otherRoom = $this->makeRoom();
        $response = $this->post(route('customer.bookings.store', $this->room), $this->payload([
            'user_id' => $otherCustomer->id, 'room_id' => $otherRoom->id,
            'status' => 'Checked In', 'total_amount' => 1,
        ]));

        $booking = Booking::sole();
        $response->assertSessionHasNoErrors()->assertRedirect(route('customer.bookings.show', $booking));
        $this->assertSame($this->customer->id, $booking->user_id);
        $this->assertSame($this->room->id, $booking->room_id);
        $this->assertSame('Pending', $booking->status);
        $this->assertEquals(225, $booking->total_amount);
        $this->assertSame('Late arrival', $booking->special_request);
        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);

        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
        $this->get(route('customer.bookings.show', $booking))->assertOk()
            ->assertSee('Your booking request has been received.')->assertSee('Pending')->assertSee('$225.00');
    }

    public function test_availability_preview_preserves_inputs_and_does_not_create_a_booking(): void
    {
        $this->get(route('customer.bookings.create', $this->room))->assertOk()
            ->assertSee('Check Availability')->assertDontSee('name="user_id"', false)
            ->assertDontSee('name="room_id"', false)->assertDontSee('name="status"', false);

        $this->post(route('customer.bookings.availability', $this->room), $this->payload([
            'user_id' => 99999, 'room_id' => 99999, 'total_amount' => 1, 'status' => 'Confirmed',
        ]))->assertOk()->assertSee('Available for your selected dates')->assertSee('$225.00')
            ->assertSee('Book Room')->assertSee('2026-10-10')->assertSee('2026-10-13')->assertSee('Late arrival');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    #[DataProvider('previewChanges')]
    public function test_final_submission_rechecks_availability_after_a_successful_preview(string $change, string $error): void
    {
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk();
        if ($change === 'overlap') {
            $this->makeBooking(['user_id' => User::factory()->create()->id]);
        } elseif ($change === 'room') {
            $this->room->update(['status' => 'maintenance']);
        } elseif ($change === 'capacity') {
            $this->room->roomType->update(['capacity' => 1]);
        } else {
            $this->customer->update(['is_active' => false]);
        }

        $count = Booking::count();
        $response = $this->from(route('customer.bookings.create', $this->room))
            ->post(route('customer.bookings.store', $this->room), $this->payload());
        if ($change === 'account') {
            $response->assertRedirect(route('login'));
            $this->assertGuest();
        } else {
            $response->assertRedirect(route('customer.bookings.create', $this->room))->assertSessionHasErrors($error);
        }
        $this->assertDatabaseCount('bookings', $count);
    }

    public static function previewChanges(): array
    {
        return [['overlap', 'room_id'], ['room', 'room_id'], ['capacity', 'number_of_guests'], ['account', '']];
    }

    public function test_final_submission_recalculates_price_after_a_preview(): void
    {
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk()->assertSee('$225.00');
        $this->room->update(['price_per_night' => 100]);
        $this->post(route('customer.bookings.store', $this->room), $this->payload(['total_amount' => 225]))
            ->assertSessionHasNoErrors();
        $this->assertEquals(300, Booking::sole()->total_amount);
    }

    #[DataProvider('activeStatuses')]
    public function test_preview_and_creation_reject_overlapping_active_bookings(string $status, string $endpoint): void
    {
        $this->makeBooking(['status' => $status]);
        $this->from(route('customer.bookings.create', $this->room))
            ->post(route('customer.bookings.'.$endpoint, $this->room), $this->payload())
            ->assertRedirect(route('customer.bookings.create', $this->room))->assertSessionHasErrors([
                'room_id' => 'This room already has an active booking during the selected dates.',
            ])->assertSessionHasInput('check_in_date', '2026-10-10')
            ->assertSessionHasInput('special_request', 'Late arrival');
        $this->assertDatabaseCount('bookings', 1);
    }

    public static function activeStatuses(): array
    {
        return [
            ['Pending', 'availability'], ['Confirmed', 'availability'], ['Checked In', 'availability'],
            ['Pending', 'store'], ['Confirmed', 'store'], ['Checked In', 'store'],
        ];
    }

    #[DataProvider('nonBlockingReservations')]
    public function test_terminal_bookings_and_checkout_boundaries_do_not_block_creation(string $status, string $checkIn): void
    {
        $this->makeBooking(['status' => $status]);
        $this->post(route('customer.bookings.store', $this->room), $this->payload([
            'check_in_date' => $checkIn, 'check_out_date' => '2026-10-15',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 2);
    }

    public static function nonBlockingReservations(): array
    {
        return [['Cancelled', '2026-10-10'], ['Checked Out', '2026-10-10'], ['Confirmed', '2026-10-13']];
    }

    #[DataProvider('invalidReservationInputs')]
    public function test_preview_and_creation_validate_dates_and_capacity(array $overrides, string $field, string $endpoint): void
    {
        $this->post(route('customer.bookings.'.$endpoint, $this->room), $this->payload($overrides))
            ->assertSessionHasErrors($field);
        $this->assertDatabaseCount('bookings', 0);
    }

    public static function invalidReservationInputs(): array
    {
        $cases = [];
        foreach ([
            [['check_in_date' => '2026-10-05'], 'check_in_date'],
            [['check_out_date' => '2026-10-10'], 'check_out_date'],
            [['check_out_date' => '2026-10-09'], 'check_out_date'],
            [['check_in_date' => 'invalid'], 'check_in_date'],
            [['number_of_guests' => 0], 'number_of_guests'],
            [['number_of_guests' => 3], 'number_of_guests'],
        ] as [$overrides, $field]) {
            foreach (['availability', 'store'] as $endpoint) {
                $cases[] = [$overrides, $field, $endpoint];
            }
        }

        return $cases;
    }

    #[DataProvider('unavailableRooms')]
    public function test_preview_and_creation_reject_operationally_unavailable_rooms(string $status, string $endpoint): void
    {
        $this->room->update(['status' => $status]);
        $this->post(route('customer.bookings.'.$endpoint, $this->room), $this->payload())
            ->assertSessionHasErrors(['room_id' => 'Please select a room with an available operational status.']);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertSame($status, $this->room->fresh()->status);
    }

    public static function unavailableRooms(): array
    {
        $cases = [];
        foreach (['booked', 'occupied', 'maintenance', 'cleaning'] as $status) {
            foreach (['availability', 'store'] as $endpoint) {
                $cases[] = [$status, $endpoint];
            }
        }

        return $cases;
    }

    #[DataProvider('unauthorizedEndpoints')]
    public function test_all_customer_endpoints_require_an_authenticated_active_customer(string $actor, string $endpoint): void
    {
        $booking = $this->makeBooking();
        if ($actor === 'guest') {
            $this->app['auth']->forgetGuards();
        } else {
            $user = $actor === 'inactive'
                ? User::factory()->inactive()->create()
                : User::factory()->create(['role' => $actor]);
            $this->actingAs($user);
        }
        $roomEndpoint = in_array($endpoint, ['create', 'availability', 'store'], true);
        $url = route('customer.bookings.'.$endpoint, $endpoint === 'index' ? [] : ($roomEndpoint ? $this->room : $booking));
        $method = match ($endpoint) {
            'availability', 'store' => 'POST', 'cancel' => 'PATCH', default => 'GET',
        };
        $response = $this->call($method, $url, $this->payload());
        if (in_array($actor, ['guest', 'inactive'], true)) {
            $response->assertRedirect(route('login'));
        } else {
            $response->assertForbidden();
        }
        $this->assertDatabaseCount('bookings', 1);
        $this->assertSame('Pending', $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public static function unauthorizedEndpoints(): array
    {
        $cases = [];
        foreach (['guest', 'staff', 'admin', 'inactive'] as $actor) {
            foreach (['create', 'availability', 'store', 'index', 'show', 'cancel'] as $endpoint) {
                $cases[$actor.' '.$endpoint] = [$actor, $endpoint];
            }
        }

        return $cases;
    }

    public function test_my_bookings_is_paginated_and_ownership_scoped(): void
    {
        $own = $this->makeBooking();
        $other = $this->makeBooking(['user_id' => User::factory()->create()->id]);
        $this->get(route('customer.bookings.index'))->assertOk()->assertSee('My Bookings')
            ->assertSee(route('customer.bookings.show', $own), false)
            ->assertDontSee(route('customer.bookings.show', $other), false)
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 1 && $bookings->first()->id === $own->id);
    }

    public function test_details_are_ownership_scoped_and_do_not_expose_management_controls(): void
    {
        $own = $this->makeBooking();
        $other = $this->makeBooking(['user_id' => User::factory()->create()->id]);
        $this->get(route('customer.bookings.show', $other))->assertNotFound();
        $response = $this->get(route('customer.bookings.show', $own))->assertOk()
            ->assertSee('Room '.$this->room->room_number)->assertSee('Double room')
            ->assertSee('2026-10-10')->assertSee('2026-10-13')->assertSee('3 nights')
            ->assertSee('$225.00')->assertSee('Pending')->assertSee('Late arrival')
            ->assertDontSee('Status History')->assertDontSee('Confirm Booking');
        foreach (['edit', 'confirm', 'check-in', 'check-out'] as $action) {
            $response->assertDontSee(route('bookings.'.$action, $own), false);
        }
    }

    #[DataProvider('cancellableStatuses')]
    public function test_customer_can_cancel_own_active_reservation_once_with_correct_history(string $status): void
    {
        $booking = $this->makeBooking(['status' => $status]);
        $this->room->update(['status' => 'maintenance']);
        $this->patch(route('customer.bookings.cancel', $booking), ['user_id' => 9999, 'status' => 'Checked In'])
            ->assertSessionHasNoErrors()->assertRedirect(route('customer.bookings.show', $booking));
        $this->assertSame('Cancelled', $booking->fresh()->status);
        $this->assertSame('maintenance', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 1);
        $this->assertDatabaseHas('booking_status_logs', [
            'booking_id' => $booking->id, 'old_status' => $status,
            'new_status' => 'Cancelled', 'changed_by' => $this->customer->id,
        ]);
        $this->patch(route('customer.bookings.cancel', $booking))->assertSessionHasErrors('status');
        $this->assertDatabaseCount('booking_status_logs', 1);
    }

    public static function cancellableStatuses(): array
    {
        return [['Pending'], ['Confirmed']];
    }

    #[DataProvider('nonCancellableStatuses')]
    public function test_customer_cannot_cancel_checked_in_or_terminal_bookings(string $status): void
    {
        $booking = $this->makeBooking(['status' => $status]);
        $this->patch(route('customer.bookings.cancel', $booking))->assertSessionHasErrors('status');
        $this->assertSame($status, $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
        $this->get(route('customer.bookings.show', $booking))->assertOk()
            ->assertDontSee(route('customer.bookings.cancel', $booking), false);
    }

    public static function nonCancellableStatuses(): array
    {
        return [['Checked In'], ['Checked Out'], ['Cancelled']];
    }

    public function test_customer_cannot_cancel_another_customers_booking(): void
    {
        $booking = $this->makeBooking(['user_id' => User::factory()->create()->id]);
        $this->patch(route('customer.bookings.cancel', $booking))->assertNotFound();
        $this->assertSame('Pending', $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public function test_customer_cancellation_rolls_back_if_history_insertion_fails(): void
    {
        $booking = $this->makeBooking();
        $event = 'eloquent.creating: '.BookingStatusLog::class;
        Event::listen($event, fn () => throw new RuntimeException('History insert failed'));
        $this->withoutExceptionHandling();
        try {
            $this->patch(route('customer.bookings.cancel', $booking));
            $this->fail('The history insert should fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('History insert failed', $exception->getMessage());
        } finally {
            Event::forget($event);
        }
        $this->assertSame('Pending', $booking->fresh()->status);
        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public function test_public_room_details_and_customer_navigation_connect_the_flow(): void
    {
        $this->get(route('rooms.show', $this->room))->assertOk()
            ->assertSee(route('customer.bookings.create', $this->room), false)->assertSee('Reserve this room');
        $this->get(route('welcome'))->assertOk()->assertSee(route('customer.bookings.index'), false);
        $this->actingAs(User::factory()->staff()->create())->get(route('rooms.show', $this->room))
            ->assertOk()->assertDontSee(route('customer.bookings.create', $this->room), false)
            ->assertDontSee(route('customer.bookings.index'), false);
    }

    public function test_guest_room_link_returns_to_the_selected_room_after_login(): void
    {
        $this->app['auth']->forgetGuards();
        $this->get(route('rooms.show', $this->room))->assertOk()
            ->assertSee(route('customer.bookings.create', $this->room), false);
        $response = $this->get(route('customer.bookings.create', $this->room))->assertRedirect(route('login'));
        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
        $this->post(route('login'), ['email' => $this->customer->email, 'password' => 'password'])
            ->assertRedirect(route('customer.bookings.create', $this->room));
    }

    private function makeRoom(): Room
    {
        $type = RoomType::create(['name' => 'Double room', 'base_price' => 75, 'capacity' => 2]);

        return Room::create([
            'room_type_id' => $type->id, 'room_number' => 'CUSTOMER-'.(Room::count() + 1),
            'price_per_night' => 75, 'status' => 'available',
        ]);
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create(array_merge($this->payload(), [
            'user_id' => $this->customer->id, 'room_id' => $this->room->id,
            'status' => 'Pending', 'total_amount' => 225,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'check_in_date' => '2026-10-10', 'check_out_date' => '2026-10-13',
            'number_of_guests' => 2, 'special_request' => 'Late arrival',
        ], $overrides);
    }
}
