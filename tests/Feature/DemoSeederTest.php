<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventBooking;
use App\Models\LoyaltyAccount;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\Payment;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoBookingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seed_is_additive_repeatable_and_has_coherent_operational_states(): void
    {
        $this->seed(DatabaseSeeder::class);

        $customer = User::where('email', 'customer@utopiabay.test')->firstOrFail();
        $this->assertSame(User::ROLE_CUSTOMER, $customer->role);
        $this->assertEquals(30, MembershipType::where('name', 'Silver')->firstOrFail()->price);
        $this->assertEquals(60, MembershipType::where('name', 'Gold')->firstOrFail()->price);
        $this->assertEquals(100, MembershipType::where('name', 'Platinum')->firstOrFail()->price);
        $this->assertDatabaseHas('memberships', ['user_id' => $customer->id, 'status' => 'active']);
        $this->assertDatabaseHas('loyalty_accounts', ['user_id' => $customer->id, 'balance' => 256]);

        foreach (DemoBookingSeeder::BOOKINGS as $marker => $details) {
            $booking = Booking::where('special_request', $marker)->firstOrFail();
            $this->assertSame($details['final_status'], $booking->status);
            $this->assertSame($details['payment_method'] === 'Card' ? 'Paid' : 'Pending', $booking->payments()->firstOrFail()->status);
        }
        $this->assertSame('occupied', Room::where('room_number', 'UB-201')->firstOrFail()->status);
        $this->assertSame('cleaning', Room::where('room_number', 'UB-202')->firstOrFail()->status);
        $this->assertSame('pending', EventBooking::where('special_requests', 'Utopia Bay demo: pending beach celebration')->firstOrFail()->status);
        $this->assertSame('approved', EventBooking::where('special_requests', 'Utopia Bay demo: approved garden wedding')->firstOrFail()->status);

        $counts = [User::count(), Room::count(), Booking::count(), Payment::count(), Membership::count(), LoyaltyAccount::count(), EventBooking::count()];
        $customer->update(['name' => 'Locally Customized Guest']);
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($counts, [User::count(), Room::count(), Booking::count(), Payment::count(), Membership::count(), LoyaltyAccount::count(), EventBooking::count()]);
        $this->assertSame('Locally Customized Guest', $customer->fresh()->name);
    }
}
