<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleListFilterTest extends TestCase
{
    use RefreshDatabase;

    private function booking(User $customer, string $status): Booking
    {
        $type = RoomType::create(['name'=>'Ocean Suite', 'capacity'=>3, 'base_price'=>75]);
        $room = Room::create(['room_type_id'=>$type->id, 'room_number'=>'SEA-'.uniqid(), 'price_per_night'=>75, 'status'=>'available']);
        return Booking::create(['user_id'=>$customer->id, 'room_id'=>$room->id, 'check_in_date'=>today()->addDay(), 'check_out_date'=>today()->addDays(3), 'number_of_guests'=>2, 'total_amount'=>150, 'status'=>$status]);
    }

    private function payment(Booking $booking, string $status, string $method, string $reference): Payment
    {
        return Payment::create(['user_id'=>$booking->user_id, 'booking_id'=>$booking->id, 'amount'=>150, 'status'=>$status, 'payment_method'=>$method, 'reference_number'=>$reference, 'payment_date'=>today()]);
    }

    public function test_booking_search_and_filters_are_grouped_and_customer_scoped(): void
    {
        $alice = User::factory()->create(['name'=>'Alice Coastal', 'email'=>'alice@example.test']);
        $bob = User::factory()->create(['name'=>'Bob Inland']);
        $wanted = $this->booking($alice, 'Confirmed');
        $this->payment($wanted, 'Paid', 'Card', 'BOOKING-PAID');
        $excluded = $this->booking($alice, 'Pending');
        $this->payment($excluded, 'Pending', 'Cash at Hotel', 'BOOKING-PENDING');
        $foreign = $this->booking($bob, 'Confirmed');
        $this->payment($foreign, 'Paid', 'Card', 'BOOKING-FOREIGN');
        $this->actingAs(User::factory()->staff()->create());
        $this->get(route('bookings.index', ['search'=>'alice', 'status'=>'Confirmed', 'payment_status'=>'Paid']))->assertOk()->assertViewHas('bookings', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $wanted->id);
        $this->get(route('bookings.index', ['search'=>'#'.$wanted->id]))->assertOk()->assertViewHas('bookings', fn ($rows) => $rows->total() === 1);
        $this->get(route('bookings.index', ['search'=>$wanted->room->room_number]))->assertOk()->assertViewHas('bookings', fn ($rows) => $rows->total() === 1);
        $this->actingAs($alice)->get(route('customer.bookings.index', ['search'=>'Ocean', 'status'=>'Confirmed', 'payment_status'=>'Paid']))->assertOk()->assertViewHas('bookings', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $wanted->id);
        $this->get(route('customer.bookings.index', ['search'=>'#'.$foreign->id]))->assertOk()->assertViewHas('bookings', fn ($rows) => $rows->isEmpty())->assertSee('Clear filters');
        Booking::whereKey($excluded->id)->update(['status'=>'Confirmed']);
        $this->get(route('customer.bookings.index', ['payment_status'=>'Paid']))->assertViewHas('bookings', fn ($rows) => $rows->total() === 1);
    }

    public function test_payment_search_filters_and_pagination_preserve_legacy_methods(): void
    {
        $alice = User::factory()->create(['name'=>'Alice Coastal']);
        $booking = $this->booking($alice, 'Pending');
        $wanted = $this->payment($booking, 'Pending', 'ABA / KHQR', 'QR-ALICE');
        $this->payment($booking, 'Paid', 'Card', 'CARD-ALICE');
        $legacy = $this->payment($booking, 'Paid', 'Bank Transfer', 'LEGACY-ALICE');
        $this->actingAs(User::factory()->staff()->create());
        $this->get(route('payments.index', ['search'=>'Alice', 'status'=>'Pending', 'payment_method'=>'ABA / KHQR']))->assertOk()->assertViewHas('payments', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $wanted->id);
        $this->get(route('payments.index', ['search'=>'#'.$booking->id]))->assertOk()->assertViewHas('payments', fn ($rows) => $rows->total() === 3);
        $this->get(route('payments.index', ['search'=>'LEGACY']))->assertOk()->assertSee('Bank Transfer')->assertViewHas('payments', fn ($rows) => $rows->first()->id === $legacy->id);
        for ($index=0; $index<11; $index++) { $this->payment($booking, 'Pending', 'ABA / KHQR', 'PAGING-'.$index); }
        $this->get(route('payments.index', ['search'=>'PAGING', 'status'=>'Pending', 'payment_method'=>'ABA / KHQR']))->assertOk()->assertViewHas('payments', fn ($rows) => $rows->total() === 11 && str_contains($rows->url(2), 'search=PAGING') && str_contains($rows->url(2), 'status=Pending'));
    }

    public function test_event_search_filters_preserve_staff_actions_and_customer_ownership(): void
    {
        $alice = User::factory()->create(['name'=>'Alice Coastal']);
        $venue = Venue::factory()->create(['name'=>'Ocean Hall']);
        $wanted = EventBooking::factory()->for($alice)->for($venue)->create(['status'=>'pending']);
        EventBooking::factory()->for($alice)->for($venue)->rejected()->create();
        $foreign = EventBooking::factory()->for($venue)->create();
        $this->actingAs(User::factory()->staff()->create());
        $this->get(route('management.event-reservations.index', ['search'=>'Alice', 'status'=>'pending', 'venue_id'=>$venue->id]))->assertOk()->assertViewHas('eventBookings', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $wanted->id)->assertSee('Edit')->assertSee('Delete');
        $this->get(route('management.event-reservations.index', ['search'=>'#'.$wanted->id]))->assertOk()->assertViewHas('eventBookings', fn ($rows) => $rows->total() === 1);
        $this->actingAs($alice)->get(route('event-reservations.index', ['search'=>'Ocean', 'status'=>'pending', 'venue_id'=>$venue->id]))->assertOk()->assertViewHas('eventBookings', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $wanted->id)->assertDontSee('Delete');
        $this->get(route('event-reservations.index', ['search'=>'#'.$foreign->id]))->assertOk()->assertViewHas('eventBookings', fn ($rows) => $rows->isEmpty())->assertSee('Clear filters');
    }
}
