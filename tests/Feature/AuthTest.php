<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_load(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/staff/login');
        $this->get('/payments')->assertRedirect('/staff/login');
    }

    public function test_customer_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Guest',
            'email' => 'guest@example.com',
            'phone' => '012 345 678',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertRedirect(route('customer.bookings.index'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'guest@example.com', 'role' => 'customer']);
    }

    public function test_registration_cannot_choose_a_role(): void
    {
        $this->post('/register', [
            'name' => 'Sneaky',
            'email' => 'sneaky@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'sneaky@example.com', 'role' => 'customer']);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('customer.bookings.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_customer_cannot_open_staff_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_staff_can_open_dashboard_but_not_user_management(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/dashboard')->assertOk();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
    }

    public function test_admin_can_create_a_staff_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'New Staff',
            'email' => 'newstaff@example.com',
            'role' => 'staff',
            'is_active' => '1',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'newstaff@example.com', 'role' => 'staff']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->delete("/admin/users/{$admin->id}");

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword123', $user->fresh()->password));
    }
}
