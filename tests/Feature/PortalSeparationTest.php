<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PortalSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_pages_and_public_navigation(): void
    {
        $this->get('/login')->assertOk()->assertSee('Guest Account')->assertDontSee('hotel operations');
        $this->get('/staff/login')->assertOk()->assertSee('Staff Sign In')->assertDontSee('Create account')->assertDontSee('Create Account')->assertDontSee('Membership');
        $this->get('/')->assertOk()->assertSee('Staff Access')->assertSee(route('staff.login'), false)->assertDontSee('Staff Portal');
        $this->get('/login')->assertDontSee('Hotel employee?')->assertDontSee('Staff Portal');
    }

    public function test_roles_can_only_authenticate_in_their_portal(): void
    {
        foreach (['customer', 'staff', 'admin'] as $role) {
            foreach (['/login', '/staff/login'] as $portal) {
                RateLimiter::clear(sha1('|127.0.0.1'));
                $user = User::factory()->create(['role' => $role]);
                $allowed = ($role === 'customer') === ($portal === '/login');
                $response = $this->post($portal, ['email' => $user->email, 'password' => 'password']);
                if ($allowed) {
                    $response->assertRedirect(route($role === 'customer' ? 'customer.bookings.index' : 'dashboard'));
                    $this->assertAuthenticatedAs($user);
                    $this->post('/logout')->assertRedirect($portal);
                } else {
                    $response->assertSessionHasErrors('email');
                }
                $this->assertGuest();
            }
        }
    }

    public function test_inactive_accounts_cannot_login_and_disabled_sessions_are_terminated(): void
    {
        foreach (['customer', 'staff', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => false]);
            $portal = $role === 'customer' ? '/login' : '/staff/login';
            $this->post($portal, ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
            $this->assertGuest();
            $this->actingAs($user)->get($role === 'customer' ? '/my-bookings' : '/dashboard')->assertRedirect($portal);
            $this->assertGuest();
        }
    }

    public function test_guests_use_the_correct_portal_and_role_authorization_remains(): void
    {
        foreach (['/dashboard', '/bookings', '/payments', '/management/rooms', '/management/venues', '/management/event-reservations', '/admin/users', '/admin/packages'] as $path) {
            $this->get($path)->assertRedirect('/staff/login');
        }
        foreach (['/my-bookings', '/memberships', '/event-reservations', '/profile'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertForbidden();
        $this->get('/management/rooms')->assertForbidden();
        foreach (['staff', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get('/my-bookings')->assertForbidden();
        }
    }

    public function test_intended_redirects_are_preserved_or_safely_ignored(): void
    {
        foreach (['customer', 'staff'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $portal = $role === 'customer' ? '/login' : '/staff/login';
            $target = route($role === 'customer' ? 'memberships.index' : 'management.rooms.index');
            $this->withSession(['url.intended' => $target])->post($portal, ['email' => $user->email, 'password' => 'password'])->assertRedirect($target);
            $this->post('/logout');
            foreach (['https://evil.example/steal', '//evil.example/steal', route($role === 'customer' ? 'dashboard' : 'customer.bookings.index')] as $unsafe) {
                RateLimiter::clear(sha1('|127.0.0.1'));
                $this->withSession(['url.intended' => $unsafe])->post($portal, ['email' => $user->email, 'password' => 'password'])->assertRedirect(route($user->homeRoute()));
                $this->post('/logout');
            }
        }
    }

    public function test_registration_forces_customer_and_preserves_intended(): void
    {
        foreach ([null, route('memberships.index')] as $intended) {
            $this->withSession(['url.intended' => $intended])->post('/register', ['name' => 'Guest', 'email' => fake()->unique()->safeEmail(), 'password' => 'Password123', 'password_confirmation' => 'Password123', 'role' => 'admin'])->assertRedirect($intended ?? route('customer.bookings.index'));
            $this->assertSame('customer', auth()->user()->role);
            $this->post('/logout');
        }
    }

    public function test_authenticated_login_visits_and_booking_navigation(): void
    {
        foreach (['customer', 'staff', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            foreach (['/login', '/staff/login'] as $path) {
                $this->actingAs($user)->get($path)->assertRedirect('/home');
                $this->get('/home')->assertRedirect(route($user->homeRoute()));
            }
        }
        $this->get('/bookings')->assertOk();
        $this->assertStringContainsString('management-nav-link active', view('partials.management-sidebar')->render());
        $this->get('/dashboard')->assertOk()->assertSee(route('bookings.index'), false);
    }

    public function test_both_login_posts_are_throttled(): void
    {
        foreach (['/login', '/staff/login'] as $path) {
            RateLimiter::clear(sha1('|127.0.0.1'));
            for ($i = 0; $i < 5; $i++) {
                $this->post($path, ['email' => 'missing@example.com', 'password' => 'wrong']);
            }
            $this->post($path, ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429);
        }
    }

    public function test_disabled_sessions_cannot_use_other_operational_routes(): void
    {
        foreach (['customer' => ['/memberships', '/event-reservations'], 'staff' => ['/payments', '/management/event-reservations'], 'admin' => ['/admin/packages', '/admin/membership-types', '/admin/users']] as $role => $paths) {
            foreach ($paths as $path) {
                $user = User::factory()->create(['role' => $role, 'is_active' => false]);
                $this->withSession(['_token' => 'old-token', 'marker' => 'private'])->actingAs($user)->get($path)->assertRedirect($role === 'customer' ? '/login' : '/staff/login');
                $this->assertGuest();
                $this->assertNull(session('marker'));
                $this->assertNotSame('old-token', session('_token'));
            }
        }
    }
}
