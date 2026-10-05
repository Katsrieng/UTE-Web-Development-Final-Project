<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingValidationTest extends TestCase
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

    #[DataProvider('overlappingDates')]
    public function test_active_bookings_block_overlapping_creation_and_update(
        string $status, string $checkIn, string $checkOut, bool $updating
    ): void {
        $this->makeBooking(['status' => $status]);
        $booking = $updating ? $this->makeBooking([
            'check_in_date' => '2026-10-20', 'check_out_date' => '2026-10-22',
        ]) : null;

        $this->submit([
            'check_in_date' => $checkIn, 'check_out_date' => $checkOut,
        ], $booking)->assertSessionHasErrors([
            'room_id' => 'This room already has an active booking during the selected dates.',
        ]);

        $this->assertDatabaseCount('bookings', $updating ? 2 : 1);
        $this->assertDatabaseCount('booking_status_logs', 0);
        if ($booking) {
            $this->assertSame('2026-10-20', $booking->fresh()->check_in_date);
        }
    }

    public static function overlappingDates(): array
    {
        $cases = [];
        foreach (['Pending', 'Confirmed', 'Checked In'] as $status) {
            foreach ([['09', '11'], ['10', '13'], ['11', '12'], ['12', '15'], ['09', '15']] as [$start, $end]) {
                foreach ([false, true] as $updating) {
                    $cases["$status $start-$end ".($updating ? 'update' : 'create')] = [
                        $status, "2026-10-$start", "2026-10-$end", $updating,
                    ];
                }
            }
        }

        return $cases;
    }

    #[DataProvider('nonBlockingDates')]
    public function test_checkout_boundaries_and_terminal_bookings_allow_creation_and_update(
        string $status, string $checkIn, string $checkOut, bool $updating
    ): void {
        $this->makeBooking(['status' => $status]);
        $booking = $updating ? $this->makeBooking([
            'check_in_date' => '2026-10-20', 'check_out_date' => '2026-10-22',
        ]) : null;

        $this->submit([
            'check_in_date' => $checkIn, 'check_out_date' => $checkOut,
        ], $booking)->assertSessionHasNoErrors()->assertRedirect(route('bookings.index'));
        $this->assertDatabaseCount('bookings', 2);
    }

    public static function nonBlockingDates(): array
    {
        $cases = [];
        foreach ([
            ['Pending', '13', '15'], ['Confirmed', '08', '10'],
            ['Cancelled', '10', '13'], ['Checked Out', '10', '13'],
        ] as [$status, $start, $end]) {
            foreach ([false, true] as $updating) {
                $cases["$status $start-$end ".($updating ? 'update' : 'create')] = [
                    $status, "2026-10-$start", "2026-10-$end", $updating,
                ];
            }
        }

        return $cases;
    }

    public function test_updating_a_booking_excludes_itself_and_does_not_create_history(): void
    {
        $booking = $this->makeBooking();
        $this->submit(['total_amount' => 1], $booking)
            ->assertSessionHasNoErrors()->assertRedirect(route('bookings.index'));

        $this->assertEquals(225, $booking->fresh()->total_amount);
        $this->assertSame('Pending', $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public function test_other_rooms_do_not_block_the_selected_room(): void
    {
        $this->makeBooking(['room_id' => $this->makeRoom()->id]);
        $this->submit()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 2);
    }

    #[DataProvider('terminalStatuses')]
    public function test_terminal_updates_are_allowed_even_when_another_booking_overlaps(string $status): void
    {
        $this->makeBooking();
        $booking = $this->makeBooking(['status' => $status]);
        $this->submit(['status' => $status], $booking)->assertSessionHasNoErrors();
        $this->assertSame($status, $booking->fresh()->status);
    }

    public static function terminalStatuses(): array
    {
        return [['Cancelled'], ['Checked Out']];
    }

    public function test_edit_cannot_reactivate_a_cancelled_booking(): void
    {
        $this->makeBooking();
        $booking = $this->makeBooking(['status' => 'Cancelled']);
        $this->submit(['status' => 'Confirmed'], $booking)->assertSessionHasErrors('status');
        $this->assertSame('Cancelled', $booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    #[DataProvider('guestCounts')]
    public function test_room_type_capacity_is_enforced_on_creation_and_update(int $guests, bool $updating): void
    {
        $booking = $updating ? $this->makeBooking() : null;
        $response = $this->submit(['number_of_guests' => $guests], $booking);

        if ($guests > 2) {
            $response->assertSessionHasErrors([
                'number_of_guests' => 'The number of guests may not exceed this room type\'s capacity of 2.',
            ]);
            $this->assertDatabaseCount('bookings', $updating ? 1 : 0);
            if ($booking) {
                $this->assertSame(1, $booking->fresh()->number_of_guests);
            }
        } else {
            $response->assertSessionHasNoErrors();
            $this->assertDatabaseHas('bookings', ['number_of_guests' => $guests]);
        }
    }

    public static function guestCounts(): array
    {
        return [[1, false], [2, false], [3, false], [1, true], [2, true], [3, true]];
    }

    #[DataProvider('ineligibleCustomers')]
    public function test_customer_eligibility_is_enforced_on_creation_and_update(string $role, bool $active, bool $updating): void
    {
        $user = User::factory()->create(['role' => $role, 'is_active' => $active]);
        $booking = $updating ? $this->makeBooking() : null;
        $this->submit(['user_id' => $user->id], $booking)->assertSessionHasErrors([
            'user_id' => 'Please select an active customer for this booking.',
        ]);
        $this->assertDatabaseCount('bookings', $updating ? 1 : 0);
        if ($booking) {
            $this->assertSame($this->customer->id, $booking->fresh()->user_id);
        }
    }

    public static function ineligibleCustomers(): array
    {
        return [
            ['customer', false, false], ['staff', true, false], ['admin', true, false],
            ['customer', false, true], ['staff', true, true], ['admin', true, true],
        ];
    }

    #[DataProvider('unavailableRooms')]
    public function test_unavailable_rooms_are_rejected_for_creation_and_room_changes(string $status, bool $updating): void
    {
        $booking = $updating ? $this->makeBooking() : null;
        $room = $this->makeRoom(['status' => $status]);
        $this->submit(['room_id' => $room->id], $booking)->assertSessionHasErrors([
            'room_id' => 'Please select a room with an available operational status.',
        ]);
        $this->assertDatabaseCount('bookings', $updating ? 1 : 0);
        $this->assertSame($status, $room->fresh()->status);
        if ($booking) {
            $this->assertSame($this->room->id, $booking->fresh()->room_id);
        }
    }

    public static function unavailableRooms(): array
    {
        $cases = [];
        foreach (['booked', 'occupied', 'maintenance', 'cleaning'] as $status) {
            $cases[] = [$status, false];
            $cases[] = [$status, true];
        }

        return $cases;
    }

    #[DataProvider('roomStatuses')]
    public function test_updates_preserve_the_current_room_regardless_of_operational_status(string $status): void
    {
        $booking = $this->makeBooking();
        $this->room->update(['status' => $status]);
        $this->submit(['special_request' => 'Late arrival'], $booking)->assertSessionHasNoErrors();
        $this->assertSame('Late arrival', $booking->fresh()->special_request);
        $this->assertSame($status, $this->room->fresh()->status);
    }

    public static function roomStatuses(): array
    {
        return [['available'], ['booked'], ['occupied'], ['maintenance'], ['cleaning']];
    }

    public function test_room_changes_recheck_capacity_overlap_and_price(): void
    {
        $booking = $this->makeBooking();
        $room = $this->makeRoom(['price_per_night' => 100]);
        $this->submit(['room_id' => $room->id, 'number_of_guests' => 3], $booking)
            ->assertSessionHasErrors('number_of_guests');

        $blocking = $this->makeBooking(['room_id' => $room->id]);
        $this->submit(['room_id' => $room->id], $booking)->assertSessionHasErrors('room_id');
        $blocking->update(['status' => 'Cancelled']);

        $this->submit(['room_id' => $room->id, 'total_amount' => 1], $booking)->assertSessionHasNoErrors();
        $this->assertSame($room->id, $booking->fresh()->room_id);
        $this->assertEquals(300, $booking->fresh()->total_amount);
        $this->assertSame('available', $room->fresh()->status);
    }

    #[DataProvider('writeModes')]
    public function test_nonexistent_customer_and_room_return_validation_errors(bool $updating): void
    {
        $booking = $updating ? $this->makeBooking() : null;
        $this->submit(['user_id' => 999999, 'room_id' => 999999], $booking)
            ->assertSessionHasErrors(['user_id', 'room_id']);
        $this->assertDatabaseCount('bookings', $updating ? 1 : 0);
    }

    public static function writeModes(): array
    {
        return [[false], [true]];
    }

    public function test_creation_uses_backend_price_and_pending_status_and_pages_still_render(): void
    {
        $this->submit([
            'check_out_date' => '2026-10-12', 'total_amount' => 1, 'status' => 'Confirmed',
        ])->assertSessionHasNoErrors()->assertRedirect(route('bookings.index'));
        $booking = Booking::firstOrFail();
        $this->assertEquals(150, $booking->total_amount);
        $this->assertSame('Pending', $booking->status);
        $this->assertSame('available', $this->room->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);

        foreach (['bookings.index', 'bookings.create', 'bookings.show', 'bookings.edit'] as $route) {
            $this->get(route($route, $booking))->assertOk();
        }
    }

    private function makeRoom(array $overrides = []): Room
    {
        $type = RoomType::create(['name' => 'Double room', 'base_price' => 75, 'capacity' => 2]);

        return Room::create(array_merge([
            'room_type_id' => $type->id, 'room_number' => 'TEST-'.(Room::count() + 1),
            'price_per_night' => 75, 'status' => 'available',
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->customer->id, 'room_id' => $this->room->id,
            'check_in_date' => '2026-10-10', 'check_out_date' => '2026-10-13',
            'number_of_guests' => 1, 'special_request' => null,
        ], $overrides);
    }

    private function makeBooking(array $overrides = []): Booking
    {
        return Booking::create($this->payload(array_merge([
            'status' => 'Pending', 'total_amount' => 225,
        ], $overrides)));
    }

    private function submit(array $overrides = [], ?Booking $booking = null): TestResponse
    {
        if ($booking) {
            return $this->from(route('bookings.edit', $booking))
                ->put(route('bookings.update', $booking), $this->payload(array_merge([
                    'status' => $booking->status,
                ], $overrides)));
        }

        return $this->from(route('bookings.create'))->post(route('bookings.store'), $this->payload($overrides));
    }
}
