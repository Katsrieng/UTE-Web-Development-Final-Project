<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_facility_status_form_accepts_database_enum_values(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post(route('management.facilities.store'), [
            'name' => 'Pool', 'description' => 'Resort pool', 'status' => 'open',
        ])->assertRedirect();
        $this->assertDatabaseHas('facilities', ['name' => 'Pool', 'status' => 'open']);

        $facility = \App\Models\Facility::firstOrFail();
        $this->put(route('management.facilities.update', $facility), [
            'name' => 'Pool', 'description' => 'Resort pool', 'status' => 'maintenance',
        ])->assertRedirect();
        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'status' => 'maintenance']);
    }

    public function test_room_type_with_booking_history_keeps_records_and_image_when_delete_is_blocked(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('room_types/history.jpg', 'demo');
        $type = RoomType::create(['name' => 'History Room', 'base_price' => 80, 'capacity' => 2, 'image' => '/storage/room_types/history.jpg']);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => 'H-101', 'floor' => 1, 'price_per_night' => 80, 'status' => 'available']);
        Booking::create(['user_id' => User::factory()->create()->id, 'room_id' => $room->id,
            'check_in_date' => today()->addDay(), 'check_out_date' => today()->addDays(2),
            'number_of_guests' => 1, 'total_amount' => 80, 'status' => 'Pending']);

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('management.room-types.index'))
            ->delete(route('management.room-types.destroy', $type))
            ->assertSessionHasErrors('room_type');

        $this->assertDatabaseHas('room_types', ['id' => $type->id]);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        Storage::disk('public')->assertExists('room_types/history.jpg');
    }

    public function test_booking_with_status_history_cannot_be_hard_deleted(): void
    {
        $type = RoomType::create(['name' => 'History Room', 'base_price' => 80, 'capacity' => 2]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => 'H-102', 'floor' => 1, 'price_per_night' => 80, 'status' => 'available']);
        $staff = User::factory()->admin()->create();
        $booking = Booking::create(['user_id' => User::factory()->create()->id, 'room_id' => $room->id,
            'check_in_date' => today()->addDay(), 'check_out_date' => today()->addDays(2),
            'number_of_guests' => 1, 'total_amount' => 80, 'status' => 'Confirmed']);
        BookingStatusLog::create(['booking_id' => $booking->id, 'changed_by' => $staff->id,
            'old_status' => 'Pending', 'new_status' => 'Confirmed']);

        $this->actingAs($staff)->from(route('bookings.show', $booking))
            ->delete(route('bookings.destroy', $booking))
            ->assertSessionHasErrors('booking');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseCount('booking_status_logs', 1);
    }

    public function test_staff_booking_details_link_to_existing_payment_instead_of_duplicate_checkout(): void
    {
        $type = RoomType::create(['name' => 'Payment Room', 'base_price' => 80, 'capacity' => 2]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => 'P-101', 'floor' => 1, 'price_per_night' => 80, 'status' => 'available']);
        $customer = User::factory()->create();
        $booking = Booking::create(['user_id' => $customer->id, 'room_id' => $room->id,
            'check_in_date' => today()->addDay(), 'check_out_date' => today()->addDays(2),
            'number_of_guests' => 1, 'total_amount' => 80, 'status' => 'Pending']);
        $payment = Payment::create(['user_id' => $customer->id, 'booking_id' => $booking->id,
            'amount' => 80, 'payment_method' => 'ABA / KHQR', 'payment_date' => today(),
            'status' => 'Pending', 'reference_number' => 'AUDIT-PAYMENT']);

        $this->actingAs(User::factory()->admin()->create())->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee(route('payments.show', $payment), false)
            ->assertDontSee('Bank Transfer');
    }
}
