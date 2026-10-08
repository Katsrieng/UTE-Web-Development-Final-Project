<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Package;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingMembershipDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-08 15:00:00');
        $this->customer = User::factory()->create();
        $type = RoomType::create(['name' => 'Suite', 'capacity' => 2, 'base_price' => 75]);
        $this->room = Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'floor' => 1, 'price_per_night' => 75, 'status' => 'available']);
        $this->actingAs($this->customer);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['check_in_date' => '2026-10-10', 'check_out_date' => '2026-10-13', 'number_of_guests' => 2], $overrides);
    }

    private function member(string $name = 'Gold', string $rate = '10.00', array $attributes = [], array $typeAttributes = []): Membership
    {
        $type = MembershipType::create(array_merge(['name' => $name, 'discount_percentage' => $rate, 'duration_months' => 12, 'status' => 'active'], $typeAttributes));
        return Membership::create(array_merge(['user_id' => $this->customer->id, 'membership_type_id' => $type->id,
            'start_date' => '2026-10-01', 'end_date' => '2027-10-01', 'status' => 'active'], $attributes));
    }

    private function submit(array $payload = [])
    {
        return $this->post(route('customer.bookings.store', $this->room), $this->payload($payload));
    }

    public static function tiers(): array
    {
        return [['Silver', '5.00', '15.25', '289.75'], ['Gold', '10.00', '30.50', '274.50'], ['Platinum', '15.00', '45.75', '259.25']];
    }

    #[DataProvider('tiers')]
    public function test_tiers_discount_full_subtotal_and_snapshot(string $name, string $rate, string $saving, string $final): void
    {
        $member = $this->member($name, $rate);
        $package = Package::create(['name' => 'Romantic', 'type' => 'romantic', 'price' => 80, 'status' => 'active']);
        $payload = ['packages' => [['package_id' => $package->id, 'quantity' => 1]]];
        $this->post(route('customer.bookings.availability', $this->room), $this->payload($payload))->assertOk()->assertSee('$'.$final)->assertSee($name.' Member');
        $this->assertDatabaseCount('bookings', 0);
        $this->submit($payload)->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $this->assertEquals($final, $booking->total_amount);
        $this->assertSame($rate, $booking->membership_discount_percentage);
        $this->assertSame($saving, $booking->membership_discount_amount);
        $this->assertSame($member->id, $booking->membership_id);
        $this->assertSame($name, $booking->membership_name);
        $this->assertEquals(80, BookingPackage::sole()->price);
    }

    public function test_no_membership_and_forged_discount_fields_are_ignored(): void
    {
        $other = $this->member(attributes: ['user_id' => User::factory()->create()->id]);
        $this->submit(['membership_id' => $other->id, 'membership_name' => 'Platinum', 'membership_discount_percentage' => 100,
            'membership_discount_amount' => 225, 'discount_percentage' => 100, 'subtotal' => 1, 'total_amount' => 0])->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $this->assertEquals(225, $booking->total_amount);
        $this->assertSame('0.00', $booking->membership_discount_percentage);
        $this->assertSame('0.00', $booking->membership_discount_amount);
        $this->assertNull($booking->membership_id);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertDontSee('Membership discount');
    }

    public static function ineligible(): array
    {
        return [[['status' => 'cancelled'], []], [['status' => 'inactive'], []], [['end_date' => '2026-10-07'], []],
            [['start_date' => '2026-10-09'], []], [[], ['status' => 'inactive']], [[], ['discount_percentage' => -1]], [[], ['discount_percentage' => 101]]];
    }

    #[DataProvider('ineligible')]
    public function test_ineligible_membership_never_discounts(array $attributes, array $typeAttributes): void
    {
        $this->member(attributes: $attributes, typeAttributes: $typeAttributes);
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk()->assertDontSee('You save');
        $this->submit()->assertSessionHasNoErrors();
        $this->assertEquals(225, Booking::sole()->total_amount);
    }

    public function test_final_submission_reloads_rate_and_eligibility(): void
    {
        $membership = $this->member();
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk()->assertSee('$202.50');
        $membership->membershipType->update(['discount_percentage' => 15]);
        $this->submit()->assertSessionHasNoErrors();
        $this->assertEquals('191.25', Booking::sole()->total_amount);
    }

    public function test_cancellation_after_preview_removes_discount_on_final_submission(): void
    {
        $membership = $this->member();
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk();
        $membership->update(['status' => 'cancelled']);
        $this->submit()->assertSessionHasNoErrors();
        $this->assertEquals(225, Booking::sole()->total_amount);
        $this->assertNull(Booking::sole()->membership_id);
    }

    public function test_historical_details_and_management_edits_use_snapshots(): void
    {
        $member = $this->member();
        $package = Package::create(['name' => 'Romantic', 'type' => 'romantic', 'price' => 80, 'status' => 'active']);
        $this->submit(['packages' => [['package_id' => $package->id, 'quantity' => 1]]])->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $member->membershipType->update(['name' => 'Renamed', 'discount_percentage' => 99]);
        $member->update(['status' => 'cancelled']);
        $package->update(['price' => 999]);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Gold')->assertSee('$225.00')->assertSee('$30.50')->assertSee('$274.50')->assertDontSee('Renamed');
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('bookings.show', $booking))->assertOk()->assertSee('Gold')->assertSee('$30.50');
        $this->put(route('bookings.update', $booking), $this->payload(['user_id' => $this->customer->id, 'room_id' => $this->room->id, 'check_out_date' => '2026-10-14', 'membership_discount_percentage' => 100]))->assertSessionHasNoErrors();
        $booking->refresh();
        $this->assertSame('10.00', $booking->membership_discount_percentage);
        $this->assertSame('38.00', $booking->membership_discount_amount);
        $this->assertEquals('342.00', $booking->total_amount);
        $member->delete();
        $this->assertNull($booking->fresh()->membership_id);
        $this->assertSame('Gold', $booking->fresh()->membership_name);
    }

    public function test_existing_zero_discount_booking_does_not_gain_discount_on_edit(): void
    {
        $this->submit()->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $this->member();
        $this->actingAs(User::factory()->admin()->create());
        $this->put(route('bookings.update', $booking), $this->payload(['user_id' => $this->customer->id, 'room_id' => $this->room->id]))->assertSessionHasNoErrors();
        $this->assertEquals(225, $booking->fresh()->total_amount);
        $this->assertSame('0.00', $booking->fresh()->membership_discount_percentage);
    }

    public function test_overlapping_legacy_memberships_choose_latest_start_then_id_without_stacking(): void
    {
        $this->member('Platinum', '15.00', ['start_date' => '2026-09-01']);
        $this->member('Silver', '5.00');
        $last = $this->member('Gold', '10.00');
        $this->submit()->assertSessionHasNoErrors();
        $this->assertSame($last->id, Booking::sole()->membership_id);
        $this->assertEquals('202.50', Booking::sole()->total_amount);
        $this->assertDatabaseCount('memberships', 3);
    }

    public static function roundingCases(): array
    {
        return [['0.00', '0.01', '0.00'], ['100.00', '0.00', '0.01'], ['50.00', '0.00', '0.01'], ['33.33', '0.01', '0.00']];
    }

    public function test_newest_valid_zero_rate_membership_does_not_fall_back_to_older_discount(): void
    {
        $this->member();
        $newest = $this->member('Zero rate', '0.00', ['start_date' => '2026-10-08']);
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk()->assertDontSee('You save');
        $this->submit()->assertSessionHasNoErrors();
        $this->assertEquals(225, Booking::sole()->total_amount);
        $this->assertSame($newest->id, Booking::sole()->membership_id);
    }

    #[DataProvider('roundingCases')]
    public function test_discount_rounds_once_half_up(string $rate, string $final, string $saving): void
    {
        $this->member(rate: $rate);
        $this->room->update(['price_per_night' => '0.01']);
        $this->submit(['check_out_date' => '2026-10-11'])->assertSessionHasNoErrors();
        $this->assertEquals($final, Booking::sole()->total_amount);
        $this->assertSame($saving, Booking::sole()->membership_discount_amount);
    }

    public function test_snapshot_and_packages_roll_back_together_on_failure(): void
    {
        $this->member();
        $package = Package::create(['name' => 'Breakfast', 'type' => 'buffet', 'price' => 15, 'status' => 'active']);
        Event::listen('eloquent.creating: '.BookingPackage::class, fn () => throw new \RuntimeException('Simulated failure'));
        try {
            $this->submit(['packages' => [['package_id' => $package->id, 'quantity' => 1]]])->assertServerError();
        } finally {
            Event::forget('eloquent.creating: '.BookingPackage::class);
        }
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_packages', 0);
        $this->assertDatabaseCount('memberships', 1);
    }
}
