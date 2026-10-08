<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBookingStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_and_payment_states_show_consistent_actions_and_real_history(): void
    {
        $customer = User::factory()->create();
        $type = RoomType::create(['name' => 'Suite', 'capacity' => 2, 'base_price' => 75]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => 'STATUS-101', 'price_per_night' => 75, 'status' => 'available']);
        $booking = Booking::create(['user_id' => $customer->id, 'room_id' => $room->id, 'check_in_date' => today()->addDay(), 'check_out_date' => today()->addDays(3), 'number_of_guests' => 2, 'total_amount' => 150, 'status' => 'Pending']);
        $this->actingAs(User::factory()->create())->get(route('customer.bookings.show', $booking))->assertNotFound();
        $this->actingAs($customer);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Payment required')->assertSee('Pay Now')->assertDontSee('Stay completed');
        $payment = Payment::create(['user_id' => $customer->id, 'booking_id' => $booking->id, 'amount' => 150, 'status' => 'Pending', 'payment_method' => 'ABA / KHQR', 'payment_date' => today(), 'reference_number' => 'CUSTOMER-STATUS']);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Complete payment submission')->assertSee('Continue Payment');
        $booking->paymentSlip()->create(['file_path' => 'slip.png', 'original_filename' => 'slip.png', 'mime_type' => 'image/png', 'reviewed' => false]);
        $this->get(route('customer.bookings.index'))->assertOk()->assertSee('Awaiting hotel verification')->assertSee('View Payment')->assertDontSee('Pay Now');
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Awaiting hotel verification')->assertDontSee('Pay Now');
        $payment->update(['payment_method' => 'Cash at Hotel']);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Pay at hotel')->assertSee('Hotel staff will collect your payment.')->assertDontSee('Receipt');
        $booking->update(['status' => 'Checked In']);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Your stay is in progress');
        $booking->update(['status' => 'Checked Out']);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Stay completed')->assertDontSee('Pay Now');
        $payment->update(['status' => 'Paid']);
        $booking->update(['status' => 'Confirmed']);
        $booking->statusLogs()->create(['old_status' => 'Pending', 'new_status' => 'Confirmed', 'changed_by' => $customer->id]);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Reservation confirmed')->assertSee('Receipt')->assertSee('Booking timeline')->assertDontSee('Checked out')->assertDontSee('Checked In');
        $booking->update(['status' => 'Cancelled']);
        $payment->update(['status' => 'Refunded']);
        $this->get(route('customer.bookings.show', $booking))->assertOk()->assertSee('Reservation cancelled')->assertSee('Refunded')->assertDontSee('Pay Now');
    }
}
