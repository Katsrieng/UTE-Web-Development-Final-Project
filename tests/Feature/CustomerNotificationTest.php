<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventBooking;
use App\Models\MembershipType;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Notifications\BookingStatusUpdatedNotification;
use App\Notifications\EventReservationStatusUpdatedNotification;
use App\Notifications\MembershipActivatedNotification;
use App\Notifications\PaymentStatusUpdatedNotification;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $otherCustomer;
    private User $admin;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-10-06 09:00:00');
        $this->customer = User::factory()->create();
        $this->otherCustomer = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $type = RoomType::create(['name' => 'Ocean Suite', 'base_price' => 75, 'capacity' => 2]);
        $this->room = Room::create(['room_type_id' => $type->id, 'room_number' => '204', 'price_per_night' => 75, 'status' => 'available']);
    }

    public function test_business_records_and_notifications_do_not_leak_between_tests(): void
    {
        foreach (['payments', 'event_bookings', 'booking_status_logs', 'bookings',
            'membership_purchases', 'memberships', 'loyalty_transactions', 'notifications'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_staff_booking_confirmation_and_cancellation_notify_only_the_owner_once(): void
    {
        $booking = $this->booking();
        app(BookingService::class)->transition((string) $booking->id, 'Confirmed', $this->admin);
        $this->assertSame(BookingStatusUpdatedNotification::class, $this->customer->notifications()->firstOrFail()->type);
        $this->assertSame('Booking confirmed', $this->customer->notifications()->first()->data['title']);
        $this->assertCount(0, $this->otherCustomer->notifications);

        app(BookingService::class)->transition((string) $booking->id, 'Cancelled', $this->admin);
        $this->assertContains('Booking cancelled', $this->customer->notifications()->get()->pluck('data')->pluck('title')->all());
        $this->assertCount(2, $this->customer->fresh()->notifications);
        $this->assertCount(0, $this->otherCustomer->fresh()->notifications);
    }

    public function test_paid_verification_and_refund_notify_once_but_initial_pending_payment_does_not(): void
    {
        $booking = $this->booking();
        $payment = app(PaymentService::class)->payBooking($this->customer, $booking->id, ['payment_method' => 'Cash at Hotel']);
        $this->assertCount(0, $this->customer->notifications);

        app(PaymentService::class)->verify($payment, $this->admin);
        app(PaymentService::class)->verify($payment, $this->admin);
        $this->assertCount(1, $this->customer->fresh()->notifications);
        $this->assertSame(PaymentStatusUpdatedNotification::class, $this->customer->notifications()->first()->type);
        $this->assertSame('Payment approved', $this->customer->notifications()->first()->data['title']);

        $this->actingAs($this->admin)->post(route('payments.refund', $payment))->assertRedirect();
        $this->post(route('payments.refund', $payment))->assertRedirect();
        $this->assertCount(2, $this->customer->fresh()->notifications);
        $this->assertContains('Payment refunded', $this->customer->notifications()->get()->pluck('data')->pluck('title')->all());
        $this->assertCount(0, $this->otherCustomer->notifications);
    }

    public function test_event_decisions_notify_owner_but_customer_self_cancellation_does_not(): void
    {
        $approved = EventBooking::factory()->for($this->customer)->create();
        $approved->approve($this->admin);
        $this->assertSame('Event reservation approved', $this->customer->notifications()->latest()->first()->data['title']);
        $rejected = EventBooking::factory()->for($this->customer)->create();
        $rejected->reject($this->admin, 'Unavailable');
        $this->assertContains('Event reservation not approved', $this->customer->notifications()->get()->pluck('data')->pluck('title')->all());
        $this->assertSame(EventReservationStatusUpdatedNotification::class, $this->customer->notifications()->first()->type);
        $selfCancelled = EventBooking::factory()->for($this->customer)->create();
        $selfCancelled->cancel($this->customer);
        $this->assertCount(2, $this->customer->fresh()->notifications);
        $this->assertCount(0, $this->otherCustomer->notifications);
    }

    public function test_staff_event_cancellation_notifies_owner(): void
    {
        $event = EventBooking::factory()->for($this->customer)->create();
        $event->cancel($this->admin);
        $this->assertSame('Event reservation cancelled', $this->customer->notifications()->firstOrFail()->data['title']);
    }

    public function test_paid_membership_activation_creates_one_meaningful_notification(): void
    {
        $type = MembershipType::create(['name' => 'Gold', 'price' => 60, 'discount_percentage' => 10,
            'duration_months' => 12, 'loyalty_upgrade_points' => 700, 'status' => 'active']);
        $payment = app(PaymentService::class)->purchaseMembership($this->customer, $type->id, ['payment_method' => 'Card']);
        $this->assertCount(1, $this->customer->fresh()->notifications);
        $notification = $this->customer->notifications()->firstOrFail();
        $this->assertSame(MembershipActivatedNotification::class, $notification->type);
        $this->assertSame('Membership activated', $notification->data['title']);
        app(PaymentService::class)->verify($payment, $this->admin);
        $this->assertCount(1, $this->customer->fresh()->notifications);
    }

    public function test_customer_bell_inbox_ownership_open_and_mark_all(): void
    {
        $booking = $this->booking();
        app(BookingService::class)->transition((string) $booking->id, 'Confirmed', $this->admin);
        $notification = $this->customer->notifications()->firstOrFail();

        $this->actingAs($this->customer)->get(route('customer.notifications.index'))->assertOk()
            ->assertSee('Notifications, 1 unread')->assertSee('Booking confirmed');
        $this->actingAs($this->otherCustomer)->get(route('customer.notifications.index'))->assertOk()
            ->assertDontSee('Booking confirmed');
        $this->post(route('customer.notifications.open', $notification->id))->assertNotFound();
        $this->post(route('customer.notifications.read', $notification->id))->assertNotFound();

        $this->actingAs($this->customer)->post(route('customer.notifications.open', $notification->id))
            ->assertRedirect(route('customer.bookings.show', $booking));
        $this->assertNotNull($notification->fresh()->read_at);
        $notification->update(['read_at' => null]);
        $this->post(route('customer.notifications.read-all'))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->get(route('customer.notifications.index', ['filter' => 'unread']))->assertOk()->assertSee('No unread notifications');

        $this->actingAs($this->admin)->get(route('customer.notifications.index'))->assertForbidden();
        $this->actingAs($this->customer)->get(route('staff.notifications.index'))->assertForbidden();
    }

    public function test_missing_related_booking_is_handled_without_exposing_other_customer_data(): void
    {
        $booking = $this->booking();
        app(BookingService::class)->transition((string) $booking->id, 'Confirmed', $this->admin);
        $notification = $this->customer->notifications()->firstOrFail();
        $booking->update(['user_id' => $this->otherCustomer->id]);

        $this->actingAs($this->customer)->post(route('customer.notifications.open', $notification->id))
            ->assertRedirect(route('customer.notifications.index'))
            ->assertSessionHas('warning', 'This update is no longer available.');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_payment_event_and_membership_notifications_open_customer_destinations(): void
    {
        $payment = app(PaymentService::class)->payBooking($this->customer, $this->booking()->id, ['payment_method' => 'Cash at Hotel']);
        app(PaymentService::class)->verify($payment, $this->admin);
        $paymentNotification = $this->customer->notifications()->where('type', PaymentStatusUpdatedNotification::class)->firstOrFail();
        $this->actingAs($this->customer)->post(route('customer.notifications.open', $paymentNotification->id))
            ->assertRedirect(route('customer.payments.show', $payment));

        $event = EventBooking::factory()->for($this->customer)->create();
        $event->approve($this->admin);
        $eventNotification = $this->customer->notifications()->where('type', EventReservationStatusUpdatedNotification::class)->firstOrFail();
        $this->post(route('customer.notifications.open', $eventNotification->id))
            ->assertRedirect(route('event-reservations.show', $event));

        $type = MembershipType::create(['name' => 'Gold', 'price' => 60, 'discount_percentage' => 10,
            'duration_months' => 12, 'loyalty_upgrade_points' => 700, 'status' => 'active']);
        app(PaymentService::class)->purchaseMembership($this->customer, $type->id, ['payment_method' => 'Card']);
        $membershipNotification = $this->customer->notifications()->where('type', MembershipActivatedNotification::class)->firstOrFail();
        $this->post(route('customer.notifications.open', $membershipNotification->id))
            ->assertRedirect(route('memberships.index'));
    }

    private function booking(): Booking
    {
        return Booking::create(['user_id' => $this->customer->id, 'room_id' => $this->room->id,
            'check_in_date' => '2026-10-10', 'check_out_date' => '2026-10-13',
            'number_of_guests' => 2, 'total_amount' => 225, 'status' => 'Pending']);
    }
}
