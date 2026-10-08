<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_daily_operations_and_preserves_financial_sections(): void
    {
        $this->travelTo('2026-10-09');
        $customer = User::factory()->create();
        $type = RoomType::create(['name' => 'Suite', 'capacity' => 2, 'base_price' => 75]);
        $rooms = [];
        foreach (['available', 'occupied', 'cleaning', 'booked'] as $index => $status) {
            $rooms[] = Room::create(['room_type_id' => $type->id, 'room_number' => 'OPS-'.$index, 'price_per_night' => 75, 'status' => $status]);
        }
        foreach (['Confirmed', 'Pending', 'Cancelled', 'Checked In'] as $status) {
            $booking = Booking::create(['user_id' => $customer->id, 'room_id' => $rooms[0]->id, 'check_in_date' => today(), 'check_out_date' => today(), 'number_of_guests' => 2, 'total_amount' => 75, 'status' => $status]);
        }
        foreach ([['Paid', '75.00', today()], ['Pending', '30.00', today()], ['Refunded', '20.00', today()], ['Paid', '25.00', today()->subDay()]] as $index => [$status, $amount, $date]) {
            Payment::create(['user_id' => $customer->id, 'booking_id' => $booking->id, 'amount' => $amount, 'payment_method' => 'Cash at Hotel', 'status' => $status, 'payment_date' => $date, 'reference_number' => 'OPS-PAY-'.$index]);
        }
        $response = $this->actingAs(User::factory()->staff()->create())->get(route('dashboard'))->assertOk();
        $this->assertCount(1, $response->viewData('arrivals'));
        $this->assertCount(1, $response->viewData('departures'));
        $this->assertCount(4, $response->viewData('recentBookings'));
        $this->get(route('bookings.index', ['status'=>'Confirmed', 'arrival_date'=>today()->toDateString()]))->assertOk()->assertViewHas('bookings', fn ($records) => $records->total() === 1 && $records->first()->status === 'Confirmed');
        $this->get(route('payments.index', ['status'=>'Pending']))->assertOk()->assertViewHas('payments', fn ($records) => $records->total() === 1 && $records->first()->status === 'Pending');
        $this->get(route('management.rooms.index', ['status'=>'cleaning']))->assertOk()->assertViewHas('rooms', fn ($records) => $records->total() === 1 && $records->first()->status === 'cleaning');
        $this->assertSame(1, $response->viewData('operations')['arrivals']);
        $this->assertSame(1, $response->viewData('operations')['departures']);
        $this->assertSame(1, $response->viewData('operations')['checked_in']);
        $this->assertSame(1, (int) $response->viewData('roomCounts')['available']);
        $this->assertEquals(75, $response->viewData('todayRevenue'));
        $this->assertEquals(100, $response->viewData('totalRevenue'));
        $response->assertSee('Hotel Operations')->assertSee("Today's Recorded Revenue", false)->assertSee('paymentStatusChart')->assertSee('Recent Bookings')->assertSee('Recent payments')->assertSee('Legacy booked');
    }
}
