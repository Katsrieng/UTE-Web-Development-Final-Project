<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MembershipEligibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-08 15:00:00');
    }

    private function tier(array $attributes = []): MembershipType
    {
        return MembershipType::create(array_merge(['name' => 'Gold', 'discount_percentage' => 10, 'duration_months' => 12, 'status' => 'active'], $attributes));
    }

    public function test_public_subscription_cannot_activate_paid_membership(): void
    {
        $customer = User::factory()->create();
        $tier = $this->tier();
        $this->actingAs($customer)->post(route('memberships.subscribe'), ['membership_type_id' => $tier->id])
            ->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseCount('memberships', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_inactive_type_cannot_be_selected(): void
    {
        $tier = $this->tier(['status' => 'inactive']);
        $this->actingAs(User::factory()->create())->post(route('memberships.subscribe'), ['membership_type_id' => $tier->id])
            ->assertSessionHasErrors('membership_type_id');
        $this->assertDatabaseCount('memberships', 0);
    }

    public static function dateAndStatusCases(): array
    {
        return [
            ['active', '2026-10-08', '2026-10-08', 'active', true],
            ['active', '2026-10-09', '2027-10-08', 'active', false],
            ['active', '2025-10-08', '2026-10-07', 'active', false],
            ['cancelled', '2026-10-08', '2027-10-08', 'active', false],
            ['inactive', '2026-10-08', '2027-10-08', 'active', false],
            ['active', '2026-10-08', '2027-10-08', 'inactive', false],
        ];
    }

    #[DataProvider('dateAndStatusCases')]
    public function test_discount_eligibility_uses_inclusive_dates_and_type_status(string $status, string $start, string $end, string $typeStatus, bool $eligible): void
    {
        $membership = Membership::create(['user_id' => User::factory()->create()->id, 'membership_type_id' => $this->tier(['status' => $typeStatus])->id,
            'start_date' => $start, 'end_date' => $end, 'status' => $status]);
        $this->assertSame($eligible ? 10.0 : 0.0, $membership->discountPercentage());
        $membership->refreshExpiry();
        if ($end === '2026-10-08') {
            $this->assertSame('active', $membership->fresh()->status);
        }
    }

    public function test_annual_plan_fields_and_admin_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = ['name' => 'Silver', 'discount_percentage' => 5, 'duration_months' => 12, 'status' => 'active', 'price' => 20, 'loyalty_upgrade_points' => 300];
        $this->actingAs($admin)->post(route('admin.membership-types.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('membership_types', ['name' => 'Silver', 'price' => 20, 'loyalty_upgrade_points' => 300]);
        $this->post(route('admin.membership-types.store'), array_merge($payload, ['price' => 0, 'loyalty_upgrade_points' => -1, 'duration_months' => 6]))
            ->assertSessionHasErrors(['price', 'loyalty_upgrade_points', 'duration_months']);
    }

    public function test_plan_page_offers_payment_checkout_and_future_loyalty_wording(): void
    {
        $type = $this->tier(['price' => 40, 'loyalty_upgrade_points' => 700]);
        $this->actingAs(User::factory()->create())->get(route('memberships.index'))->assertOk()
            ->assertSee('$40.00')->assertSee('Purchase Membership')->assertSee('700')
            ->assertSee(route('customer.payments.membership', $type))->assertDontSee('Join Gold');
    }

    public function test_cancelled_latest_record_does_not_hide_current_legacy_membership(): void
    {
        $customer = User::factory()->create();
        $tier = $this->tier();
        Membership::create(['user_id' => $customer->id, 'membership_type_id' => $tier->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']);
        Membership::create(['user_id' => $customer->id, 'membership_type_id' => $tier->id, 'start_date' => '2026-02-01', 'end_date' => '2027-12-31', 'status' => 'cancelled']);
        $this->actingAs($customer)->get(route('memberships.index'))->assertOk()->assertSee('Active membership');
        $this->assertDatabaseCount('memberships', 2);
    }

    public function test_membership_cancellation_is_owned_and_records_are_preserved(): void
    {
        $owner = User::factory()->create();
        $membership = Membership::create(['user_id' => $owner->id, 'membership_type_id' => $this->tier()->id, 'start_date' => '2026-10-08', 'end_date' => '2027-10-08', 'status' => 'active']);
        $this->actingAs(User::factory()->create())->post(route('memberships.cancel', $membership))->assertForbidden();
        $this->actingAs($owner)->post(route('memberships.cancel', $membership))->assertRedirect();
        $this->assertSame('cancelled', $membership->fresh()->status);
    }

    public function test_additive_migrations_preserve_legacy_rows_and_initialize_only_plan_metadata(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.membership_migration_probe' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('membership_migration_probe');
        try {
            $oldTypes = require database_path('migrations/2024_01_01_000001_create_membership_types_table.php');
            $oldTypes->up();
            foreach (['Silver', 'Gold', 'Platinum', 'Custom'] as $name) {
                DB::table('membership_types')->insert(['name' => $name, 'discount_percentage' => 7.5, 'duration_months' => 6, 'status' => 'inactive']);
            }
            Schema::create('memberships', fn ($table) => $table->id());
            Schema::create('bookings', function ($table) {
                $table->id();
                $table->decimal('total_amount', 10, 2);
            });
            DB::table('bookings')->insert(['total_amount' => 225]);
            $plans = require database_path('migrations/2026_10_08_000002_add_paid_plan_fields_to_membership_types_table.php');
            $plans->up();
            $snapshots = require database_path('migrations/2026_10_08_000003_add_membership_discount_snapshots_to_bookings_table.php');
            $snapshots->up();
            $this->assertSame(4, DB::table('membership_types')->count());
            foreach (['Silver' => [20, 300], 'Gold' => [40, 700], 'Platinum' => [70, null], 'Custom' => [null, null]] as $name => [$price, $points]) {
                $type = DB::table('membership_types')->where('name', $name)->first();
                $this->assertEquals($price, $type->price);
                $this->assertSame($points, $type->loyalty_upgrade_points);
                $this->assertEquals(7.5, $type->discount_percentage);
                $this->assertSame(6, $type->duration_months);
                $this->assertSame('inactive', $type->status);
            }
            $booking = DB::table('bookings')->sole();
            $this->assertEquals(225, $booking->total_amount);
            $this->assertEquals(0, $booking->membership_discount_amount);
            $this->assertEquals(0, $booking->membership_discount_percentage);
            $this->assertNull($booking->membership_id);
            $this->assertNull($booking->membership_name);
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('membership_migration_probe');
        }
    }
}
