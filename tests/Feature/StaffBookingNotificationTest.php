<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\NewBookingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class StaffBookingNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-06 09:00:00');
        $this->customer = User::factory()->create(['name' => 'Sokha Customer']);
        $type = RoomType::create(['name' => 'Ocean Suite', 'base_price' => 75, 'capacity' => 2]);
        $this->room = Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'price_per_night' => 75, 'status' => 'available']);
    }

    public function test_successful_customer_booking_notifies_only_active_authorized_staff_with_correct_payload(): void
    {
        $admin = User::factory()->admin()->create();
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);
        $staff = User::factory()->staff()->create();
        $inactive = User::factory()->staff()->inactive()->create();
        $unauthorized = User::factory()->staff()->create();
        Role::where('slug', 'staff')->firstOrFail()->permissions()->detach();
        $this->actingAs($this->customer)->post(route('customer.bookings.store', $this->room), $this->payload())->assertRedirect();
        $booking = Booking::sole();

        $this->assertCount(1, $admin->notifications);
        $this->assertCount(1, $manager->notifications);
        $this->assertCount(0, $staff->notifications);
        $this->assertCount(0, $inactive->notifications);
        $this->assertCount(0, $unauthorized->notifications);
        $this->assertCount(0, $this->customer->notifications);
        $this->actingAs($unauthorized)->get(route('staff.notifications.index'))->assertForbidden();
        $notification = $admin->notifications->first();
        $this->assertSame(NewBookingNotification::class, $notification->type);
        $this->assertSame($booking->id, $notification->data['booking_id']);
        $this->assertSame('Sokha Customer', $notification->data['customer_name']);
        $this->assertStringContainsString('101', $notification->data['room']);
        $this->assertSame('2026-10-10', $notification->data['check_in']);
        $this->assertSame(2, $notification->data['guests']);
        $this->assertEquals(225, $notification->data['total_amount']);
        $this->assertSame('Pending', $notification->data['status']);
    }

    public function test_front_desk_with_booking_permission_receives_only_after_final_submission(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($this->customer)->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk();
        $this->assertCount(0, $staff->notifications);

        $this->post(route('customer.bookings.store', $this->room), $this->payload())->assertRedirect();
        $this->assertCount(1, $staff->fresh()->notifications);
    }

    public function test_staff_bell_unread_count_dropdown_and_customer_isolation(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->customer)->post(route('customer.bookings.store', $this->room), $this->payload())->assertRedirect();

        $this->actingAs($admin)->get(route('staff.notifications.index'))->assertOk()
            ->assertSee('Notifications')->assertSee('Sokha Customer')->assertSee('1 unread');
        $this->actingAs($this->customer)->get(route('customer.bookings.index'))->assertOk()
            ->assertDontSee(route('staff.notifications.index'));
        $this->get(route('staff.notifications.index'))->assertForbidden();
    }

    public function test_open_marks_read_and_redirects_to_booking_and_is_owned_by_staff_member(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $this->actingAs($this->customer)->post(route('customer.bookings.store', $this->room), $this->payload());
        $booking = Booking::sole();
        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($otherAdmin)->post(route('staff.notifications.open', $notification->id))->assertNotFound();
        $this->actingAs($admin)->post(route('staff.notifications.open', $notification->id))
            ->assertRedirect(route('bookings.show', $booking));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->get(route('staff.notifications.index', ['filter' => 'unread']))->assertOk()->assertSee('No unread notifications');
    }

    public function test_mark_read_mark_all_and_missing_booking_are_safe(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->customer)->post(route('customer.bookings.store', $this->room), $this->payload());
        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)->post(route('staff.notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $notification->update(['read_at' => null]);
        $this->post(route('staff.notifications.read-all'))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $notification->update(['read_at' => null]);
        Booking::query()->delete();
        $this->post(route('staff.notifications.open', $notification->id))
            ->assertRedirect(route('staff.notifications.index'))
            ->assertSessionHas('warning', 'This booking is no longer available.');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_failed_booking_and_rolled_back_booking_do_not_send_notifications(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($this->customer)->post(route('customer.bookings.store', $this->room), $this->payload(['number_of_guests' => 10]))
            ->assertSessionHasErrors();
        $this->assertCount(0, $admin->notifications);

        try {
            DB::transaction(function () use ($admin): void {
                $this->post(route('customer.bookings.store', $this->room), $this->payload())->assertRedirect();
                $this->assertCount(0, $admin->fresh()->notifications);
                throw new RuntimeException('Force rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Force rollback', $exception->getMessage());
        }
        $this->assertDatabaseCount('bookings', 0);
        $this->assertCount(0, $admin->fresh()->notifications);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['check_in_date' => '2026-10-10', 'check_out_date' => '2026-10-13', 'number_of_guests' => 2], $overrides);
    }
}
