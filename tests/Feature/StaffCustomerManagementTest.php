<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffCustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_staff_accounts_list_excludes_customers_and_rejects_customer_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Unique Customer']);
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('Staff Accounts')->assertDontSee('Unique Customer');
        $this->get(route('admin.users.edit', $customer))->assertNotFound();
        $this->post(route('admin.users.store'), [
            'name' => 'Forged Customer', 'email' => 'forged@example.test', 'role' => 'customer',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'forged@example.test']);
    }

    public function test_customer_list_filters_and_customer_only_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $active = User::factory()->create(['role' => 'customer', 'name' => 'Anika Guest', 'is_active' => true]);
        $inactive = User::factory()->create(['role' => 'customer', 'name' => 'Bora Guest', 'is_active' => false]);
        $this->actingAs($admin)->get(route('management.customers.index', ['q' => 'Anika', 'active' => '1']))
            ->assertOk()->assertSee('Anika Guest')->assertDontSee('Bora Guest');
        $this->get(route('management.customers.show', $active))->assertOk()->assertSee('Anika Guest');
        $this->get(route('management.customers.show', $admin))->assertNotFound();
        $this->assertNotNull($inactive);
    }

    public function test_customer_permissions_and_role_forgery_are_enforced(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $staff = User::factory()->create(['role' => 'staff']);
        $manager = User::factory()->create(['role' => 'manager']);
        $this->actingAs($customer)->get(route('management.customers.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('management.customers.index'))->assertOk();
        $this->get(route('management.customers.edit', $customer))->assertForbidden();
        $this->delete(route('management.customers.destroy', $customer))->assertForbidden();
        $this->actingAs($manager)->put(route('management.customers.update', $customer), [
            'name' => 'Changed', 'email' => $customer->email, 'is_active' => 1, 'role' => 'admin',
        ])->assertSessionHasErrors('role');
        $this->assertSame('customer', $customer->refresh()->role);
    }

    public function test_manager_can_update_but_only_admin_can_delete_customer_accounts(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($manager)->put(route('management.customers.update', $customer), [
            'name' => 'Updated Guest', 'email' => $customer->email, 'is_active' => 0,
        ])->assertRedirect(route('management.customers.show', $customer));
        $this->assertSame('Updated Guest', $customer->refresh()->name);
        $this->assertFalse($customer->is_active);
        $this->delete(route('management.customers.destroy', $customer))->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->delete(route('management.customers.destroy', $customer))->assertRedirect(route('management.customers.index'));
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_customer_with_business_history_cannot_be_deleted_and_permission_revocation_takes_effect(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $customer = User::factory()->create(['role' => 'customer']);
        EventBooking::factory()->for($customer)->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']))->delete(route('management.customers.destroy', $customer))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $customer->id]);
        Role::where('slug', 'manager')->firstOrFail()->permissions()->detach();
        $this->actingAs($manager)->get(route('management.customers.index'))->assertForbidden();
    }
}
