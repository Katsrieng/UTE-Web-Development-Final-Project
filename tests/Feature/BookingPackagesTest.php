<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPackage;
use App\Models\Package;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BookingPackagesTest extends TestCase
{
    use RefreshDatabase;

    private Room $room;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-10');
        $type = RoomType::create(['name' => 'Add-on Suite', 'capacity' => 2, 'base_price' => 75]);
        $this->room = Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'floor' => 1, 'price_per_night' => 75, 'status' => 'available']);
        $this->customer = User::factory()->create();
        $this->actingAs($this->customer);
    }

    private function package(array $overrides = []): Package
    {
        return Package::create(array_merge(['name' => 'Breakfast', 'type' => 'buffet', 'price' => '15.00', 'status' => 'active'], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['check_in_date' => '2026-10-11', 'check_out_date' => '2026-10-14', 'number_of_guests' => 2], $overrides);
    }

    private function submit(array $overrides = [])
    {
        return $this->post(route('customer.bookings.store', $this->room), $this->payload($overrides));
    }

    public function test_zero_packages_and_empty_catalogue_work(): void
    {
        $this->get(route('customer.bookings.create', $this->room))->assertOk();
        $this->submit()->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals(225, Booking::sole()->total_amount);
        $this->assertDatabaseCount('booking_packages', 0);
        $this->get(route('customer.bookings.show', Booking::sole()))->assertOk();
    }

    public function test_multiple_packages_quantities_and_forged_prices(): void
    {
        $one = $this->package();
        $two = $this->package(['name' => 'Romantic', 'price' => '80.00']);
        $this->submit(['user_id' => 999, 'room_id' => 999, 'status' => 'Confirmed', 'total_amount' => 1, 'package_total' => 1, 'packages' => [['package_id' => $one->id, 'quantity' => 2, 'price' => 0.01], ['package_id' => $two->id, 'quantity' => 1]]])->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $this->assertEquals(335, $booking->total_amount);
        $this->assertSame('Pending', $booking->status);
        $this->assertSame($this->customer->id, $booking->user_id);
        $this->assertCount(2, $booking->bookingPackages);
        $this->assertCount(2, $booking->packages);
        $this->assertEquals('15.00', $booking->bookingPackages->first()->price);
        $this->assertSame(2, $booking->bookingPackages->first()->quantity);
        $this->assertSame($booking->id, $one->bookings->first()->id);
    }

    #[DataProvider('invalidSelections')]
    public function test_invalid_package_selections_are_rejected(mixed $selection, string $field): void
    {
        $this->package();
        $this->submit(['packages' => $selection])->assertSessionHasErrors($field);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_packages', 0);
    }

    public static function invalidSelections(): array
    {
        return [
            ['bad', 'packages'],
            [['bad'], 'packages.0'],
            [[['quantity' => 2]], 'packages.0.package_id'],
            [[['package_id' => 999, 'quantity' => 1]], 'packages.0.package_id'],
            [[['package_id' => 1, 'quantity' => 0]], 'packages.0.quantity'],
            [[['package_id' => 1, 'quantity' => 11]], 'packages.0.quantity'],
            [[['package_id' => 1, 'quantity' => 1.5]], 'packages.0.quantity'],
            [[['package_id' => 1, 'quantity' => 'bad']], 'packages.0.quantity'],
            [[['package_id' => 1, 'quantity' => []]], 'packages.0.quantity'],
            [[['package_id' => 1, 'quantity' => 1], ['package_id' => '1', 'quantity' => 2]], 'packages.0.package_id'],
        ];
    }

    public function test_inactive_packages_are_hidden_and_rejected(): void
    {
        $package = $this->package(['name' => 'Inactive offer', 'status' => 'inactive']);
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk()->assertDontSee('Inactive offer');
        $this->submit(['packages' => [['package_id' => $package->id, 'quantity' => 1]]])->assertSessionHasErrors('packages.0.package_id');
    }

    public function test_preview_is_read_only_and_final_price_is_revalidated(): void
    {
        $package = $this->package();
        $selection = ['packages' => [['package_id' => $package->id, 'quantity' => 2]]];
        $this->post(route('customer.bookings.availability', $this->room), $this->payload($selection))->assertOk()->assertSee('$255.00');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_packages', 0);
        $package->update(['price' => '20.00']);
        $this->submit($selection)->assertSessionHasNoErrors();
        $this->assertEquals(265, Booking::sole()->total_amount);
        $this->assertEquals('20.00', BookingPackage::sole()->price);
    }

    public function test_package_deactivated_after_preview_is_rejected(): void
    {
        $package = $this->package();
        $selection = ['packages' => [['package_id' => $package->id, 'quantity' => 1]]];
        $this->post(route('customer.bookings.availability', $this->room), $this->payload($selection))->assertOk();
        $package->update(['status' => 'inactive']);
        $this->submit($selection)->assertSessionHasErrors('packages.0.package_id');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_snapshots_details_and_management_updates_preserve_charges(): void
    {
        $package = $this->package();
        $this->submit(['packages' => [['package_id' => $package->id, 'quantity' => 2]]]);
        $booking = Booking::sole();
        $package->update(['price' => 99, 'status' => 'inactive']);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Breakfast')->assertSee('$15.00')->assertSee('$30.00')->assertSee('$255.00');
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('bookings.show', $booking))->assertOk()->assertSee('Breakfast')->assertSee('$15.00');
        $this->put(route('bookings.update', $booking), $this->payload(['user_id' => $this->customer->id, 'room_id' => $this->room->id, 'check_out_date' => '2026-10-15', 'packages' => []]))->assertSessionHasNoErrors();
        $this->assertEquals(330, $booking->fresh()->total_amount);
        $this->assertEquals('15.00', BookingPackage::sole()->price);
        $this->assertSame(2, BookingPackage::sole()->quantity);
        $this->delete(route('admin.packages.destroy', $package))->assertSessionHas('error');
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
    }

    public function test_pivot_failure_rolls_back_entire_booking(): void
    {
        $one = $this->package();
        $two = $this->package();
        $count = 0;
        Event::listen('eloquent.creating: '.BookingPackage::class, function () use (&$count) {
            if (++$count === 2) {
                throw new \RuntimeException('Simulated pivot failure');
            }
        });
        try {
            $this->submit(['packages' => [['package_id' => $one->id, 'quantity' => 1], ['package_id' => $two->id, 'quantity' => 1]]])->assertServerError();
        } finally {
            Event::forget('eloquent.creating: '.BookingPackage::class);
        }
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_packages', 0);
    }

    public function test_default_quantity_and_maximum_quantity_use_cents(): void
    {
        $one = $this->package(['price' => '0.10']);
        $two = $this->package(['price' => '0.20', 'type' => 'other']);
        $this->submit(['packages' => [['package_id' => $one->id], ['package_id' => $two->id, 'quantity' => 10]]])->assertSessionHasNoErrors();
        $this->assertEquals('227.10', Booking::sole()->total_amount);
    }

    public function test_deferred_foreign_key_exists_and_migration_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_10_08_000001_add_booking_packages_booking_foreign_key.php');
        $migration->up();
        $foreignKeys = Schema::getForeignKeys('booking_packages');
        $this->assertCount(1, array_filter($foreignKeys, fn ($key) => $key['columns'] === ['booking_id'] && $key['foreign_table'] === 'bookings'));
    }

    public function test_package_deleted_after_preview_is_rejected(): void
    {
        $package = $this->package();
        $selection = ['packages' => [['package_id' => $package->id, 'quantity' => 1]]];
        $this->post(route('customer.bookings.availability', $this->room), $this->payload($selection))->assertOk();
        $package->delete();
        $this->submit($selection)->assertSessionHasErrors('packages.0.package_id');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_unselected_quantity_is_not_submitted_and_selection_ui_is_scoped_to_preview(): void
    {
        $package = $this->package(['description' => 'Breakfast for your stay']);
        $this->get(route('customer.bookings.create', $this->room))->assertOk()->assertDontSee('Enhance your stay');
        $this->post(route('customer.bookings.availability', $this->room), $this->payload())->assertOk()->assertSee('Enhance your stay')->assertSee('Check Availability')->assertSee('Breakfast for your stay')->assertSee('data-package-selection', false);
        $this->submit(['packages' => [], 'package_quantities' => [$package->id => 999]])->assertSessionHasNoErrors();
        $this->assertEquals(225, Booking::sole()->total_amount);
    }

    public function test_schema_enforces_package_restrict_and_booking_cascade(): void
    {
        $package = $this->package();
        $this->submit(['packages' => [['package_id' => $package->id, 'quantity' => 1]]]);
        try {
            $package->delete();
            $this->fail('Expected package foreign key restriction.');
        } catch (QueryException $exception) {
            $this->assertDatabaseHas('packages', ['id' => $package->id]);
        }
        Booking::sole()->delete();
        $this->assertDatabaseCount('booking_packages', 0);
    }

    public function test_fresh_table_creation_does_not_depend_on_booking_table(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.migration_probe' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::setDefaultConnection('migration_probe');
        try {
            $packages = require database_path('migrations/2024_01_01_000002_create_packages_table.php');
            $packages->up();
            $pivot = require database_path('migrations/2026_01_03_000001_create_booking_packages_table.php');
            $pivot->up();
            $this->assertFalse(Schema::hasTable('bookings'));
            $this->assertCount(0, array_filter(Schema::getForeignKeys('booking_packages'), fn ($key) => $key['columns'] === ['booking_id']));
            Schema::create('bookings', fn ($table) => $table->id());
            $deferred = require database_path('migrations/2026_10_08_000001_add_booking_packages_booking_foreign_key.php');
            $deferred->up();
            $this->assertCount(1, array_filter(Schema::getForeignKeys('booking_packages'), fn ($key) => $key['columns'] === ['booking_id']));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('migration_probe');
        }
    }

    public function test_excessive_total_is_rejected_without_partial_records(): void
    {
        $package = $this->package(['price' => '99999999.99', 'type' => 'other']);
        $this->submit(['packages' => [['package_id' => $package->id, 'quantity' => 10]]])->assertSessionHasErrors('total_amount');
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_packages', 0);
    }

    public function test_malformed_package_id_recovers_without_a_server_error(): void
    {
        $this->package();
        $this->followingRedirects()->submit(['packages' => [['package_id' => [1], 'quantity' => 1]]])->assertOk()->assertSee('Please correct');
        $this->assertDatabaseCount('bookings', 0);
    }

    public static function typeQuantities(): array
    {
        return [
            ['buffet', 2, true], ['buffet', 1, true], ['buffet', 10, false],
            ['romantic', 1, true], ['romantic', 2, false],
            ['family', 1, true], ['family', 2, false],
            ['accommodation', 1, true], ['accommodation', 2, false],
            ['other', 10, true], ['other', 11, false],
        ];
    }

    #[DataProvider('typeQuantities')]
    public function test_type_quantity_rules_apply_to_preview_and_final(string $type, int $quantity, bool $accepted): void
    {
        $package = $this->package(['type' => $type]);
        $selection = ['packages' => [['package_id' => $package->id, 'quantity' => $quantity]]];
        $preview = $this->post(route('customer.bookings.availability', $this->room), $this->payload($selection));
        if ($accepted) {
            $preview->assertOk()->assertSessionHasNoErrors();
            $limit = $type === 'buffet' ? 2 : ($type === 'other' ? 10 : 1);
            if ($limit === 1) {
                $preview->assertSee('id="quantity-'.$package->id.'" type="hidden"', false);
                $preview->assertDontSee('data-quantity-target="quantity-'.$package->id.'" aria-label="Increase', false);
            } else {
                $preview->assertSee('type="number" min="1" max="'.$limit.'"', false);
            }
            $this->submit($selection)->assertSessionHasNoErrors();
            $this->assertSame($quantity, BookingPackage::sole()->quantity);
            $this->assertEquals(225 + 15 * $quantity, Booking::sole()->total_amount);
        } else {
            $preview->assertSessionHasErrors('packages.0.quantity');
            $this->submit($selection)->assertSessionHasErrors('packages.0.quantity');
            $this->assertDatabaseCount('bookings', 0);
            $this->assertDatabaseCount('booking_packages', 0);
        }
    }

    public function test_final_booking_reloads_type_after_preview(): void
    {
        $package = $this->package(['type' => 'other']);
        $selection = ['packages' => [['package_id' => $package->id, 'quantity' => 10]]];
        $this->post(route('customer.bookings.availability', $this->room), $this->payload($selection))->assertOk();
        $package->update(['type' => 'buffet']);
        $this->submit($selection)->assertSessionHasErrors('packages.0.quantity');
        $this->assertDatabaseCount('bookings', 0);
    }
    public function test_buffet_limit_uses_guests_even_above_ten(): void
    {
        $this->room->roomType->update(['capacity' => 12]);
        $package = $this->package();
        $selection = ['number_of_guests' => 12, 'packages' => [['package_id' => $package->id, 'quantity' => 12]]];
        $this->post(route('customer.bookings.availability', $this->room), $this->payload($selection))->assertOk()->assertSee('max="12"', false);
        $this->submit($selection)->assertSessionHasNoErrors();
        $this->assertSame(12, BookingPackage::sole()->quantity);
    }
}
