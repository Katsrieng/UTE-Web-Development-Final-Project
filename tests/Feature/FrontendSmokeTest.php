<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_hotel_pages_render_with_the_shared_theme(): void
    {
        $this->get('/')->assertOk()->assertSee('Stay beautifully');
        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->get('/register')->assertOk()->assertSee('Your stay starts here');
        $this->get('/rooms')->assertOk()->assertSee('Rooms made for real comfort');
        $this->get('/room-types')->assertOk()->assertSee('Room types for every stay');
        $this->get('/facilities')->assertOk()->assertSee('Facilities designed around you');
        $this->get('/venues')->assertOk()->assertSee('A venue for every occasion');
    }

    public function test_homepage_links_have_distinct_customer_destinations_without_overlays(): void
    {
        $html = $this->get(route('welcome'))->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $links = new \DOMXPath($document);

        foreach ([
            'Explore rooms' => 'rooms.index',
            'Plan an event' => 'venues.index',
            'Find a room' => 'rooms.index',
            'Host an event' => 'venues.index',
            'Discover facilities' => 'facilities.index',
            'Comfortable stays' => 'rooms.index',
            'Memorable events' => 'venues.index',
            'Guest facilities' => 'facilities.index',
            'Member benefits' => 'memberships.index',
            'Create account' => 'register',
            'Sign in' => 'login',
        ] as $label => $routeName) {
            $matches = $links->query('//main//a[contains(normalize-space(.), "'.$label.'")]');
            $this->assertCount(1, $matches, "Expected one homepage link labeled {$label}.");
            $this->assertSame(route($routeName), $matches->item(0)->getAttribute('href'));
        }

        $this->assertCount(0, $links->query('//a//a'));
        $this->assertCount(0, $links->query('//main//a[contains(concat(" ", normalize-space(@class), " "), " stretched-link ")]'));

        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('welcome'))->assertOk()
            ->assertDontSee('href="'.route('memberships.index').'"', false);

        $inactiveCustomer = User::factory()->inactive()->create();
        $this->actingAs($inactiveCustomer)->get(route('welcome'))->assertOk()
            ->assertDontSee('href="'.route('customer.bookings.index').'"', false);
    }

    public function test_public_room_and_facility_detail_pages_render(): void
    {
        $roomType = RoomType::create([
            'name' => 'Deluxe Suite',
            'base_price' => 80,
            'capacity' => 2,
            'bed_type' => 'King Bed',
        ]);

        $room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => '201',
            'floor' => 2,
            'price_per_night' => 95,
            'status' => 'available',
        ]);

        $facility = Facility::create([
            'name' => 'High-Speed Wi-Fi',
            'description' => 'Complimentary wireless internet.',
            'status' => 'open',
        ]);

        $this->get(route('rooms.show', $room))->assertOk()->assertSee('Room 201');
        $this->get(route('room-types.show', $roomType))->assertOk()->assertSee('Deluxe Suite');
        $this->get(route('facilities.show', $facility))->assertOk()->assertSee('High-Speed Wi-Fi');
    }

    public function test_customer_membership_and_profile_pages_render(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('memberships.index'))
            ->assertOk()
            ->assertSee('Utopia Bay membership');

        $this->actingAs($customer)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('My Profile');
    }

    public function test_admin_management_pages_render_in_the_management_shell(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, '.$admin->name)
            ->assertSee('No payment data yet')
            ->assertSee('Quick actions');

        $routes = [
            'dashboard',
            'management.rooms.index',
            'management.rooms.create',
            'management.room-types.index',
            'management.room-types.create',
            'management.facilities.index',
            'management.facilities.create',
            'management.venues.index',
            'management.venues.create',
            'management.event-reservations.index',
            'payments.index',
            'payments.create',
            'admin.users.index',
            'admin.users.create',
            'admin.membership-types.index',
            'admin.membership-types.create',
            'admin.packages.index',
            'admin.packages.create',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($admin)->get(route($routeName));

            $this->assertSame(
                200,
                $response->status(),
                "The [{$routeName}] management page did not render successfully."
            );
            $response->assertSee('Hotel operations');
        }
    }

    public function test_public_top_navigation_keeps_management_users_in_the_public_shell(): void
    {
        $admin = User::factory()->admin()->create();

        $home = $this->actingAs($admin)->get(route('welcome'));

        $home->assertOk()
            ->assertSee(route('rooms.index'), false)
            ->assertSee(route('facilities.index'), false);

        foreach (['rooms.index', 'room-types.index', 'facilities.index'] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee('Main navigation')
                ->assertDontSee('Hotel operations');
        }
    }

    public function test_staff_cannot_access_admin_membership_or_package_management(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('admin.membership-types.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.packages.index'))->assertForbidden();
    }
}
