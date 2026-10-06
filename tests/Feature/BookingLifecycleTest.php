<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class BookingLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $staff;
    private User $customer;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-06 09:00:00');
        $this->staff = User::factory()->staff()->create();
        $this->customer = User::factory()->create();
        $this->room = $this->makeRoom();
        $this->actingAs($this->staff);
    }

    #[DataProvider('validTransitions')]
    public function test_valid_actions_update_status_room_and_exactly_one_history_entry(
        string $oldStatus, string $action, string $newStatus, string $initialRoomStatus, string $finalRoomStatus, string $role
    ): void {
        $actor = $role === 'admin' ? User::factory()->admin()->create() : $this->staff;
        $this->room->update(['status' => $initialRoomStatus]);
        $booking = $this->makeBooking($oldStatus);

        $this->actingAs($actor)->from(route('bookings.show', $booking))
            ->patch(route('bookings.'.$action, $booking), ['status' => 'Cancelled'])
            ->assertSessionHasNoErrors()->assertRedirect(route('bookings.show', $booking))
            ->assertSessionHas('success');

        $this->assertSame($newStatus, $booking->fresh()->status);
        $this->assertSame($finalRoomStatus, $this->room->fresh()->status);
        $this->assertEquals(225, $booking->fresh()->total_amount);
        $this->assertDatabaseCount('booking_status_logs', 1);
        $log = BookingStatusLog::sole();
        $this->assertSame($booking->id, $log->booking_id);
        $this->assertSame($actor->id, $log->changed_by);
        $this->assertSame($oldStatus, $log->old_status);
        $this->assertSame($newStatus, $log->new_status);
        $this->assertNotNull($log->created_at);

        // Repeating an action cannot duplicate its history or apply room changes twice.
        $this->patch(route('bookings.'.$action, $booking))->assertSessionHasErrors('status');
        $this->assertSame($newStatus, $booking->fresh()->status);
        $this->assertSame($finalRoomStatus, $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 1);
    }

    public static function validTransitions(): array
    {
        $cases = [];
        foreach (['staff', 'admin'] as $role) {
            foreach ([
                ['Pending', 'confirm', 'Confirmed', 'available', 'available'],
                ['Pending', 'cancel', 'Cancelled', 'available', 'available'],
                ['Confirmed', 'check-in', 'Checked In', 'available', 'occupied'],
                ['Confirmed', 'cancel', 'Cancelled', 'available', 'available'],
                ['Checked In', 'check-out', 'Checked Out', 'occupied', 'cleaning'],
            ] as $transition) {
                $cases[$role.' '.$transition[1].' from '.$transition[0]] = [...$transition, $role];
            }
        }

        return $cases;
    }

    public function test_complete_stay_preserves_history_and_prevents_another_guest_using_the_room(): void
    {
        $booking = $this->makeBooking('Pending');
        $nextBooking = Booking::create($this->payload([
            'status' => 'Confirmed', 'total_amount' => 150,
            'check_in_date' => '2026-10-13', 'check_out_date' => '2026-10-15',
        ]));

        $this->patch(route('bookings.confirm', $booking))->assertSessionHasNoErrors();
        $this->patch(route('bookings.check-in', $booking))->assertSessionHasNoErrors();
        $this->patch(route('bookings.check-in', $nextBooking))->assertSessionHasErrors('room_id');
        $this->assertSame('Confirmed', $nextBooking->fresh()->status);
        $this->assertSame('occupied', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 2);

        $this->patch(route('bookings.check-out', $booking))->assertSessionHasNoErrors();
        $this->patch(route('bookings.check-in', $nextBooking))->assertSessionHasErrors('room_id');
        $this->assertSame('cleaning', $this->room->fresh()->status);
        $this->assertSame('Checked Out', $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 3);
        $this->assertSame(
            ['Confirmed', 'Checked In', 'Checked Out'],
            $booking->statusLogs()->orderBy('id')->pluck('new_status')->all()
        );
    }

    #[DataProvider('invalidTransitions')]
    public function test_invalid_transitions_change_nothing(string $status, string $action): void
    {
        $booking = $this->makeBooking($status);
        $this->from(route('bookings.show', $booking))->patch(route('bookings.'.$action, $booking))
            ->assertRedirect(route('bookings.show', $booking))->assertSessionHasErrors('status');

        $this->assertSame($status, $booking->fresh()->status);
        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public static function invalidTransitions(): array
    {
        $allowed = [
            'Pending' => ['confirm', 'cancel'],
            'Confirmed' => ['check-in', 'cancel'],
            'Checked In' => ['check-out'],
            'Checked Out' => [],
            'Cancelled' => [],
        ];
        $cases = [];
        foreach ($allowed as $status => $actions) {
            foreach (['confirm', 'cancel', 'check-in', 'check-out'] as $action) {
                if (! in_array($action, $actions, true)) {
                    $cases[$status.' '.$action] = [$status, $action];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('unavailableRoomStatuses')]
    public function test_check_in_requires_an_available_room(string $status): void
    {
        $booking = $this->makeBooking('Confirmed');
        $this->room->update(['status' => $status]);
        $this->patch(route('bookings.check-in', $booking))->assertSessionHasErrors([
            'room_id' => 'Check-in requires a room with an available operational status.',
        ]);

        $this->assertSame('Confirmed', $booking->fresh()->status);
        $this->assertSame($status, $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public static function unavailableRoomStatuses(): array
    {
        return [['booked'], ['occupied'], ['maintenance'], ['cleaning']];
    }

    #[DataProvider('reservationOnlyActions')]
    public function test_confirm_and_cancel_preserve_every_operational_room_status(string $status, string $action): void
    {
        $booking = $this->makeBooking('Pending');
        $this->room->update(['status' => $status]);
        $this->patch(route('bookings.'.$action, $booking))->assertSessionHasNoErrors();
        $this->assertSame($status, $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 1);
    }

    public static function reservationOnlyActions(): array
    {
        $cases = [];
        foreach (['available', 'booked', 'occupied', 'maintenance', 'cleaning'] as $status) {
            foreach (['confirm', 'cancel'] as $action) {
                $cases[] = [$status, $action];
            }
        }

        return $cases;
    }

    #[DataProvider('actionSources')]
    public function test_guests_and_customers_cannot_use_management_actions(string $action, string $status): void
    {
        $booking = $this->makeBooking($status);
        $this->app['auth']->forgetGuards();
        $this->patch(route('bookings.'.$action, $booking))->assertRedirect(route('staff.login'));
        $this->actingAs($this->customer)->patch(route('bookings.'.$action, $booking))->assertForbidden();

        $this->assertSame($status, $booking->fresh()->status);
        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public static function actionSources(): array
    {
        return [['confirm', 'Pending'], ['cancel', 'Pending'], ['check-in', 'Confirmed'], ['check-out', 'Checked In']];
    }

    #[DataProvider('actionSources')]
    public function test_history_failure_rolls_back_booking_and_room_changes(string $action, string $status): void
    {
        $booking = $this->makeBooking($status);
        $roomStatus = $status === 'Checked In' ? 'occupied' : 'available';
        $this->room->update(['status' => $roomStatus]);
        $outerTransactionLevel = DB::transactionLevel();
        $event = 'eloquent.creating: '.BookingStatusLog::class;
        Event::listen($event, function () use ($outerTransactionLevel) {
            $this->assertGreaterThan($outerTransactionLevel, DB::transactionLevel());
            throw new RuntimeException('Simulated history insert failure');
        });

        $this->withoutExceptionHandling();
        try {
            $this->patch(route('bookings.'.$action, $booking));
            $this->fail('The history failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated history insert failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        $this->assertSame($status, $booking->fresh()->status);
        $this->assertSame($roomStatus, $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
        $this->assertSame($outerTransactionLevel, DB::transactionLevel());
    }

    #[DataProvider('detailStatuses')]
    public function test_details_show_only_actions_allowed_by_the_lifecycle(string $status, array $actions): void
    {
        $booking = $this->makeBooking($status);
        $response = $this->get(route('bookings.show', $booking))->assertOk();
        foreach (['confirm', 'cancel', 'check-in', 'check-out'] as $action) {
            if (in_array($action, $actions, true)) {
                $response->assertSee(route('bookings.'.$action, $booking), false);
            } else {
                $response->assertDontSee(route('bookings.'.$action, $booking), false);
            }
        }
    }

    public static function detailStatuses(): array
    {
        return [
            ['Pending', ['confirm', 'cancel']], ['Confirmed', ['check-in', 'cancel']],
            ['Checked In', ['check-out']], ['Checked Out', []], ['Cancelled', []],
        ];
    }

    public function test_details_display_action_success_and_validation_errors(): void
    {
        $booking = $this->makeBooking('Pending');
        $response = $this->patch(route('bookings.confirm', $booking))->assertSessionHas('success');
        $this->withCookie(config('session.cookie'), $response->getCookie(config('session.cookie'))->getValue());
        $this->get(route('bookings.show', $booking))->assertSee('Booking confirmed successfully.');
        $this->from(route('bookings.show', $booking))->patch(route('bookings.confirm', $booking))
            ->assertSessionHasErrors('status');
        $this->get(route('bookings.show', $booking))
            ->assertSee('Cannot change booking status from Confirmed to Confirmed.');
    }

    public function test_edit_has_read_only_status_and_allows_updates_without_status_input(): void
    {
        $booking = $this->makeBooking('Confirmed');
        $this->get(route('bookings.edit', $booking))->assertOk()
            ->assertDontSee('name="status"', false)->assertSee('Manage status from Booking Details.');
        $this->put(route('bookings.update', $booking), $this->payload(['special_request' => 'Late arrival']))
            ->assertSessionHasNoErrors()->assertRedirect(route('bookings.index'));

        $this->assertSame('Confirmed', $booking->fresh()->status);
        $this->assertSame('Late arrival', $booking->fresh()->special_request);
        $this->assertEquals(225, $booking->fresh()->total_amount);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    #[DataProvider('forgedStatuses')]
    public function test_edit_cannot_change_status_even_for_an_otherwise_valid_transition(string $source, string $target): void
    {
        $booking = $this->makeBooking($source);
        $this->put(route('bookings.update', $booking), $this->payload([
            'status' => $target, 'special_request' => 'Should not save',
        ]))->assertSessionHasErrors([
            'status' => 'Manage booking status using the actions on Booking Details.',
        ]);

        $this->assertSame($source, $booking->fresh()->status);
        $this->assertNull($booking->fresh()->special_request);
        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public static function forgedStatuses(): array
    {
        return [
            ['Pending', 'Confirmed'], ['Pending', 'Checked In'], ['Confirmed', 'Checked In'],
            ['Checked In', 'Checked Out'], ['Checked Out', 'Confirmed'], ['Cancelled', 'Pending'],
        ];
    }

    public function test_checked_in_booking_cannot_be_reassigned_but_other_information_can_be_edited(): void
    {
        $booking = $this->makeBooking('Checked In');
        $this->room->update(['status' => 'occupied']);
        $otherRoom = $this->makeRoom();
        $this->put(route('bookings.update', $booking), $this->payload(['room_id' => $otherRoom->id]))
            ->assertSessionHasErrors(['room_id' => 'The room cannot be changed while this booking is Checked In.']);
        $this->assertSame($this->room->id, $booking->fresh()->room_id);
        $this->assertSame('occupied', $this->room->fresh()->status);
        $this->assertSame('available', $otherRoom->fresh()->status);

        $this->get(route('bookings.edit', $booking))->assertOk()->assertSee('Room reassignment is unavailable while Checked In.');
        $this->put(route('bookings.update', $booking), $this->payload(['special_request' => 'Extra towels']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Extra towels', $booking->fresh()->special_request);
        $this->assertSame('Checked In', $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    private function makeRoom(): Room
    {
        $type = RoomType::create(['name' => 'Double room', 'base_price' => 75, 'capacity' => 2]);

        return Room::create([
            'room_type_id' => $type->id, 'room_number' => 'LIFE-'.(Room::count() + 1),
            'price_per_night' => 75, 'status' => 'available',
        ]);
    }

    private function makeBooking(string $status): Booking
    {
        return Booking::create($this->payload(['status' => $status, 'total_amount' => 225]));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->customer->id, 'room_id' => $this->room->id,
            'check_in_date' => '2026-10-10', 'check_out_date' => '2026-10-13',
            'number_of_guests' => 1, 'special_request' => null,
        ], $overrides);
    }
}
