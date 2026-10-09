<?php

namespace Tests\Feature;

use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventReservationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_edit_and_safe_delete_preserve_validation_payments_and_history(): void
    {
        $this->grantStaffPermissions('delete_event_reservations');
        $this->travelTo('2026-10-09 09:00:00');
        $staff = User::factory()->staff()->create();
        $venue = Venue::factory()->create(['capacity'=>50, 'price'=>500, 'event_types'=>[Venue::EVENT_TYPE_MEETING]]);
        $booking = EventBooking::factory()->for($venue)->create(['starts_at'=>'2026-10-20 10:00:00', 'ends_at'=>'2026-10-20 12:00:00']);
        $data = ['venue_id'=>$venue->id, 'event_type'=>Venue::EVENT_TYPE_MEETING, 'event_date'=>'2026-10-21', 'start_time'=>'10:00', 'end_time'=>'12:00', 'guest_count'=>30, 'special_requests'=>'Updated setup', 'status'=>'approved', 'user_id'=>$staff->id, 'quoted_price'=>1];
        $this->actingAs($booking->user)->get(route('management.event-reservations.edit', $booking))->assertForbidden();
        $this->patch(route('management.event-reservations.update', $booking), $data)->assertForbidden();
        $this->delete(route('management.event-reservations.destroy', $booking))->assertForbidden();
        $this->actingAs($staff)->get(route('management.event-reservations.edit', $booking))->assertOk()->assertSee('Save Changes');
        $this->patch(route('management.event-reservations.update', $booking), $data)->assertRedirect(route('management.event-reservations.show', $booking));
        $booking->refresh();
        $this->assertSame(30, $booking->guest_count);
        $this->assertSame('pending', $booking->status);
        $this->assertNotEquals($staff->id, $booking->user_id);
        $this->assertNotEquals(1, $booking->quoted_price);
        $this->patch(route('management.event-reservations.update', $booking), array_replace($data, ['guest_count'=>51]))->assertSessionHasErrors('guest_count');
        $this->patch(route('management.event-reservations.update', $booking), array_replace($data, ['end_time'=>'09:00']))->assertSessionHasErrors('end_time');
        EventBooking::factory()->for($venue)->create(['starts_at'=>'2026-10-22 10:00:00', 'ends_at'=>'2026-10-22 12:00:00']);
        $this->patch(route('management.event-reservations.update', $booking), array_replace($data, ['event_date'=>'2026-10-22']))->assertSessionHasErrors('reservation');
        $venue->update(['is_active'=>false]);
        $this->patch(route('management.event-reservations.update', $booking), $data)->assertSessionHasErrors('venue_id');
        $venue->update(['is_active'=>true]);
        $newVenue = Venue::factory()->create(['capacity'=>50, 'price'=>650, 'event_types'=>[Venue::EVENT_TYPE_MEETING]]);
        $this->patch(route('management.event-reservations.update', $booking), array_replace($data, ['venue_id'=>$newVenue->id]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(650, $booking->refresh()->quoted_price);
        $this->assertSame($newVenue->id, $booking->venue_id);
        $this->patch(route('management.event-reservations.update', $booking), array_replace($data, ['venue_id'=>$newVenue->id, 'event_date'=>'2026-10-08']))->assertSessionHasErrors('event_date');
        $this->patch(route('management.event-reservations.update', $booking), array_replace($data, ['venue_id'=>$newVenue->id, 'event_type'=>'wedding']))->assertSessionHasErrors('event_type');
        $payment = Payment::create(['user_id'=>$booking->user_id, 'event_booking_id'=>$booking->id, 'amount'=>500, 'payment_method'=>'Cash at Hotel', 'status'=>'Pending', 'payment_date'=>today(), 'reference_number'=>'EVENT-SAFE']);
        $this->get(route('management.event-reservations.edit', $booking))->assertRedirect()->assertSessionHas('error');
        $this->patch(route('management.event-reservations.update', $booking), $data)->assertRedirect()->assertSessionHas('error');
        $this->delete(route('management.event-reservations.destroy', $booking))->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('payments', ['id'=>$payment->id, 'event_booking_id'=>$booking->id]);
        foreach (['approved', 'rejected', 'cancelled'] as $status) {
            $historical = EventBooking::factory()->create(['status'=>$status, 'processed_at'=>now()]);
            $this->delete(route('management.event-reservations.destroy', $historical))->assertSessionHas('error');
            $this->patch(route('management.event-reservations.update', $historical), $data)->assertSessionHas('error');
            $this->assertDatabaseHas('event_bookings', ['id'=>$historical->id]);
        }
        foreach (['pending', 'rejected', 'cancelled'] as $status) {
            $obsolete = EventBooking::factory()->create(['status'=>$status]);
            $this->delete(route('management.event-reservations.destroy', $obsolete))->assertRedirect(route('management.event-reservations.index'));
            $this->assertDatabaseMissing('event_bookings', ['id'=>$obsolete->id]);
        }
        $this->get(route('management.event-reservations.index'))->assertOk();
        $this->get(route('management.event-reservations.show', $booking))->assertOk()->assertSee('linked payments');
    }
}
