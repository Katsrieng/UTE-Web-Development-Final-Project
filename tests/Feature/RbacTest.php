<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\EventBooking;
use App\Models\Payment;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_is_protected_and_non_admins_cannot_manage_roles(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $this->assertTrue($admin->hasPermission('refund_payments'));
        $this->assertTrue($admin->hasPermission('any_future_permission'));
        $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->assertSee('Administrator access is protected.');
        $this->patch(route('admin.roles.update', Role::where('slug','admin')->first()), ['permissions'=>[]])->assertForbidden();
        foreach (['manager','staff','customer'] as $slug) {
            $this->actingAs(User::factory()->create(['role'=>$slug]))->get(route('admin.roles.index'))->assertForbidden();
        }
    }

    public function test_admin_can_change_role_permissions_and_revocation_is_immediate(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        foreach (['manager','staff'] as $slug) {
            $user = User::factory()->create(['role'=>$slug]);
            $role = Role::where('slug',$slug)->first();
            $this->actingAs($admin)->patch(route('admin.roles.update',$role), ['permissions'=>['view_dashboard','view_payments','refund_payments']])->assertRedirect();
            $this->assertTrue($user->hasPermission('refund_payments'));
            $this->actingAs($user)->get(route('payments.index'))->assertOk();
            $this->actingAs($admin)->patch(route('admin.roles.update',$role), ['permissions'=>['view_dashboard']])->assertRedirect();
            $this->assertFalse($user->hasPermission('view_payments'));
            $this->actingAs($user)->get(route('payments.index'))->assertForbidden();
            $this->get(route('admin.users.create'))->assertForbidden();
            $this->patch(route('admin.roles.update',$role), ['permissions'=>['manage_users']])->assertForbidden();
        }
    }

    public function test_staff_defaults_protect_mutations_and_hide_forbidden_actions(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('dashboard'))->assertOk()->assertDontSee('Roles &amp; Permissions',false);
        $this->get(route('payment-settings.edit'))->assertForbidden();
        $this->get(route('management.rooms.create'))->assertForbidden();
        $this->get(route('admin.membership-types.index'))->assertForbidden();
        $this->get(route('bookings.create'))->assertOk();
        $payment = Payment::create(['user_id'=>User::factory()->create()->id,'amount'=>30,'payment_method'=>'Cash at Hotel','status'=>'Paid','payment_date'=>today(),'reference_number'=>'RBAC-PAID']);
        $this->post(route('payments.refund',$payment))->assertForbidden();
        $this->get(route('payments.show',$payment))->assertOk()->assertDontSee('Mark as Refunded');
        $this->assertDatabaseHas('payments',['id'=>$payment->id,'status'=>'Paid']);
        $event = EventBooking::factory()->create();
        $this->delete(route('management.event-reservations.destroy',$event))->assertForbidden();
    }

    public function test_admin_creates_staff_and_manager_and_staff_cannot_promote_themselves(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        foreach (['staff','manager'] as $role) {
            $this->actingAs($admin)->post(route('admin.users.store'), ['name'=>'Hotel '.$role,'email'=>$role.'@example.test','phone'=>'012345678','role'=>$role,'is_active'=>1,'password'=>'Password123','password_confirmation'=>'Password123'])->assertRedirect();
            $user = User::where('email',$role.'@example.test')->firstOrFail();
            $this->assertTrue(Hash::check('Password123',$user->password));
            $this->actingAs($user)->put(route('admin.users.update',$user),['role'=>'admin'])->assertForbidden();
            $this->assertSame($role,$user->refresh()->role);
        }
    }

    public function test_public_registration_ignores_role_and_permissions(): void
    {
        $this->post('/register', ['name'=>'Public customer','email'=>'public@example.test','password'=>'Password123','password_confirmation'=>'Password123','role'=>'admin','role_id'=>1,'permissions'=>['manage_users']])->assertRedirect();
        $customer = User::where('email','public@example.test')->firstOrFail();
        $this->assertSame('customer',$customer->role);
        $this->assertFalse($customer->hasPermission('manage_users'));
        $this->post(route('logout'));
        $this->get('/staff/register')->assertNotFound();
    }

    public function test_seeding_is_idempotent_and_retains_customized_permissions(): void
    {
        $role = Role::where('slug','staff')->first();
        $role->permissions()->sync([]);
        $this->seed(RolePermissionSeeder::class);
        $this->assertSame(3,Role::count());
        $this->assertSame(0,$role->permissions()->count());
    }

    public function test_historical_staff_cannot_be_deleted_and_self_admin_is_protected(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $staff = User::factory()->staff()->create();
        EventBooking::factory()->create(['status'=>'approved','processed_by'=>$staff->id,'processed_at'=>now()]);
        $this->actingAs($admin)->delete(route('admin.users.destroy',$staff))->assertSessionHas('error');
        $this->assertDatabaseHas('users',['id'=>$staff->id]);
        $this->put(route('admin.users.update',$staff),['name'=>$staff->name,'email'=>$staff->email,'role'=>'staff','is_active'=>0])->assertSessionHasNoErrors();
        $this->assertFalse($staff->refresh()->is_active);
        $this->put(route('admin.users.update',$admin),['name'=>$admin->name,'email'=>$admin->email,'role'=>'staff','is_active'=>0])->assertRedirect();
        $this->assertSame('admin',$admin->refresh()->role);
        $this->assertTrue($admin->is_active);
        $this->delete(route('admin.users.destroy',$admin))->assertSessionHas('error');
    }

    public function test_permission_matrix_covers_module_routes_and_reserves_administration(): void
    {
        $manager = User::factory()->create(['role'=>'manager']);
        $this->actingAs($manager);
        foreach (['bookings.index','management.rooms.index','management.room-types.index','management.facilities.index','payments.index','management.event-reservations.index','management.venues.index','admin.membership-types.index','admin.packages.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $admin = User::factory()->create(['role'=>'admin']);
        $role = Role::where('slug','manager')->first();
        $this->actingAs($admin)->patch(route('admin.roles.update',$role),['permissions'=>['manage_users']])->assertStatus(422);
        $this->patch(route('admin.roles.update',$role),['permissions'=>['view_payments','manage_payments']])->assertRedirect();
        $customer = User::factory()->create();
        $event = EventBooking::factory()->for($customer)->create();
        $this->actingAs($manager)->post(route('payments.store'),['event_booking_id'=>$event->id,'user_id'=>$customer->id,'amount'=>30,'payment_method'=>'Cash at Hotel','status'=>'Paid','payment_date'=>today()->toDateString(),'reference_number'=>'FORGED-VERIFY'])->assertForbidden();
        $this->assertDatabaseMissing('payments',['reference_number'=>'FORGED-VERIFY']);
        $this->put(route('profile.update'),['name'=>$manager->name,'email'=>$manager->email,'role'=>'admin'])->assertRedirect();
        $this->assertSame('manager',$manager->refresh()->role);
        $this->actingAs($admin)->patch(route('admin.roles.update',$role),['permissions'=>['view_rooms','manage_room_gallery']])->assertRedirect();
        $type = \App\Models\RoomType::create(['name'=>'RBAC Suite','capacity'=>2,'base_price'=>75]);
        $room = \App\Models\Room::create(['room_type_id'=>$type->id,'room_number'=>'RBAC-ROOM','price_per_night'=>75,'status'=>'available']);
        $this->actingAs($manager)->get(route('management.rooms.edit',$room))->assertOk()->assertSee('Room Photos')->assertDontSee('Save Changes');
        $this->put(route('management.rooms.update',$room),[])->assertForbidden();
    }

    public function test_manager_uses_staff_portal_and_cannot_use_customer_login(): void
    {
        User::factory()->create(['email'=>'manager-login@example.test','role'=>'manager','password'=>'Password123']);
        $this->post('/login',['email'=>'manager-login@example.test','password'=>'Password123'])->assertSessionHasErrors('email');
        $this->post(route('staff.login.store'),['email'=>'manager-login@example.test','password'=>'Password123'])->assertRedirect(route('dashboard'));
        $this->get(route('management.venues.index'))->assertOk();
        $this->get(route('admin.membership-types.index'))->assertOk();
        $this->get(route('payment-settings.edit'))->assertForbidden();
    }
}
