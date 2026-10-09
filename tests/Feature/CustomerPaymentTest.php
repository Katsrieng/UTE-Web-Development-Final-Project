<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Payment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerPaymentTest extends TestCase
{
    use RefreshDatabase;
    private User $customer;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('payment-settings/test-qr.png', 'test QR');
        \App\Models\PaymentSetting::create(['account_name' => 'Utopia Bay Resort', 'khqr_image' => 'payment-settings/test-qr.png', 'aba_khqr_enabled' => true]);
        $this->customer = User::factory()->create();
        $type = RoomType::create(['name' => 'Suite', 'capacity' => 2, 'base_price' => 75]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => '101', 'floor' => 1, 'price_per_night' => 75, 'status' => 'available']);
        $this->booking = Booking::create(['user_id' => $this->customer->id, 'room_id' => $room->id, 'check_in_date' => today()->addDay(), 'check_out_date' => today()->addDays(4), 'number_of_guests' => 2, 'total_amount' => 274.50, 'status' => 'Pending']);
        $this->actingAs($this->customer);
    }

    public static function methods(): array
    {
        return [['Cash at Hotel', 'Pending'], ['ABA / KHQR', 'Pending'], ['Card', 'Paid']];
    }

    public function test_customer_checkout_and_payment_results_render_clear_method_states(): void
    {
        $this->get(route('customer.payments.booking', $this->booking))->assertOk()
            ->assertSee('Room stay')->assertSee('Final total')->assertSee('$274.50')->assertSee('Payment Summary')
            ->assertSee('Demo card payment')->assertSee('Pay at the hotel')->assertSee('Confirm Pay at Hotel')
            ->assertSee('Pay with ABA / KHQR')->assertSee('payment-settings/test-qr.png');
        $plan = \App\Models\MembershipType::create(['name' => 'Gold', 'price' => 60, 'discount_percentage' => 10,
            'duration_months' => 12, 'loyalty_upgrade_points' => 700, 'status' => 'active']);
        $this->get(route('customer.payments.membership', $plan))->assertOk()->assertSee('Gold Membership')->assertSee('$60.00')->assertSee('700');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'ABA / KHQR',
            'payment_slip' => \Illuminate\Http\UploadedFile::fake()->image('submitted.png')])->assertSessionHasNoErrors();
        $payment = Payment::sole();
        $this->get(route('customer.payments.show', $payment))->assertOk()->assertSee('Payment Submitted')->assertSee('Awaiting hotel verification')->assertSee('submitted.png');
        $this->actingAs(User::factory()->staff()->create())->post(route('payments.verify', $payment))->assertSessionHasNoErrors();
        $this->actingAs($this->customer)->get(route('customer.payments.show', $payment))->assertOk()->assertSee('Payment Successful')->assertSee('Your booking is now confirmed.');
    }

    public function test_khqr_checkout_reuses_slips_and_confirms_only_after_staff_verification(): void
    {
        $this->get(route('customer.bookings.show', $this->booking))->assertOk()->assertSee('Pay Now')->assertDontSee('images/qr-khqr.png');
        $this->get(route('customer.payments.booking', $this->booking))->assertOk()->assertSee('payment-settings/test-qr.png')->assertSee('payment_slip');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'ABA / KHQR',
            'payment_slip' => \Illuminate\Http\UploadedFile::fake()->create('bad.pdf', 20, 'application/pdf')])->assertSessionHasErrors('payment_slip');
        $this->assertDatabaseCount('payments', 0);
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'ABA / KHQR',
            'payment_slip' => \Illuminate\Http\UploadedFile::fake()->image('slip.png')])->assertSessionHasNoErrors();
        $payment = Payment::sole();
        $slip = $this->booking->paymentSlip()->firstOrFail();
        $this->assertSame('Pending', $payment->status);
        $this->assertSame('Pending', $this->booking->fresh()->status);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($slip->file_path);
        $this->get(route('customer.payments.booking', $this->booking))->assertOk()->assertSee('slip.png');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'ABA / KHQR',
            'payment_slip' => \Illuminate\Http\UploadedFile::fake()->image('replacement.jpg')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('booking_payment_slips', 1);
        $this->assertSame('replacement.jpg', $this->booking->paymentSlip()->first()->original_filename);
        $this->delete(route('customer.bookings.payment-slip.destroy', $this->booking))->assertRedirect(route('customer.payments.booking', $this->booking));
        $this->assertDatabaseCount('booking_payment_slips', 0);
        $this->actingAs(User::factory()->create());
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'ABA / KHQR',
            'payment_slip' => \Illuminate\Http\UploadedFile::fake()->image('forged.png')])->assertNotFound();
        $this->actingAs(User::factory()->staff()->create());
        $this->post(route('payments.verify', $payment))->assertSessionHasNoErrors();
        $this->assertSame('Paid', $payment->fresh()->status);
        $this->assertSame('Confirmed', $this->booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 1);
    }

    #[DataProvider('methods')]
    public function test_customer_method_and_authoritative_amount(string $method, string $status): void
    {
        $this->get(route('customer.payments.booking', $this->booking))->assertOk()->assertSee('$274.50')->assertSee('Demo card payment');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => $method, 'transaction_reference' => 'DEMO-TRANSFER', 'amount' => 1, 'user_id' => 999, 'status' => 'Paid', 'booking_id' => 999, 'payment_slip' => $method === 'ABA / KHQR' ? \Illuminate\Http\UploadedFile::fake()->image('slip.png') : null])->assertSessionHasNoErrors()->assertRedirect();
        $payment = Payment::sole();
        $this->assertSame('274.50', $payment->amount);
        $this->assertSame($this->customer->id, $payment->user_id);
        $this->assertSame($status, $payment->status);
        $this->assertSame($status === 'Paid' ? 'Confirmed' : 'Pending', $this->booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', $status === 'Paid' ? 1 : 0);
        $this->get(route('customer.payments.show', $payment))->assertOk();
        $this->get(route('customer.payments.receipt', $payment))->assertOk()->assertSee('Payment Receipt');
    }

    public function test_cross_customer_booking_payment_and_receipt_access_returns_404(): void
    {
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Card']);
        $payment = Payment::sole();
        $this->actingAs(User::factory()->create());
        $this->get(route('customer.payments.booking', $this->booking))->assertNotFound();
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Card'])->assertNotFound();
        $this->get(route('customer.payments.show', $payment))->assertNotFound();
        $this->get(route('customer.payments.receipt', $payment))->assertNotFound();
    }

    public function test_repeated_submission_does_not_duplicate_payment_or_confirmation(): void
    {
        foreach ([1, 2] as $attempt) {
            $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Card'])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('booking_status_logs', 1);
    }

    public function test_transfer_requires_reference_and_invalid_method_is_rejected(): void
    {
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'ABA / KHQR'])->assertSessionHasErrors('payment_slip');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Bank Transfer', 'transaction_reference' => 'OLD-METHOD'])->assertSessionHasErrors('payment_method');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Stripe'])->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_legacy_bank_transfer_records_still_display(): void
    {
        $this->get(route('customer.payments.booking', $this->booking))->assertOk()->assertDontSee('Bank Transfer');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Cash at Hotel']);
        $payment = Payment::sole();
        $payment->update(['payment_method' => 'Bank Transfer']);
        $this->actingAs(User::factory()->staff()->create());
        $this->get(route('payments.index'))->assertOk()->assertSee('Bank Transfer');
        $this->get(route('payments.show', $payment))->assertOk()->assertSee('Bank Transfer');
        $this->get(route('payments.receipt', $payment))->assertOk()->assertSee('Bank Transfer');
    }

    public function test_cancelled_and_checked_out_bookings_cannot_be_paid(): void
    {
        foreach (['Cancelled', 'Checked Out'] as $status) {
            $this->booking->update(['status' => $status]);
            $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Card'])->assertSessionHasErrors('payment');
        }
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_staff_verification_confirms_once_refund_does_not_cancel_booking(): void
    {
        $this->grantStaffPermissions('refund_payments');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Cash at Hotel']);
        $payment = Payment::sole();
        $this->actingAs(User::factory()->staff()->create());
        $this->post(route('payments.verify', $payment))->assertSessionHasNoErrors();
        $this->post(route('payments.verify', $payment))->assertSessionHasNoErrors();
        $this->assertSame('Paid', $payment->fresh()->status);
        $this->assertSame('Confirmed', $this->booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 1);
        $this->post(route('payments.refund', $payment))->assertRedirect();
        $this->assertSame('Refunded', $payment->fresh()->status);
        $this->assertSame('Confirmed', $this->booking->fresh()->status);
    }

    public function test_card_fields_are_not_part_of_form_payload_or_storage(): void
    {
        $html = $this->get(route('customer.payments.booking', $this->booking))->assertOk();
        foreach (['card_number', 'cvv', 'expiry_date', 'cardholder_name'] as $field) {
            $html->assertDontSee('name="'.$field.'"', false);
        }
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Card', 'card_number' => '4242424242424242', 'cvv' => '123', 'expiry_date' => '12/30'])->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('4242424242424242', Payment::sole()->toJson());
        $this->assertStringNotContainsString('card_number', Payment::sole()->toJson());
    }

    public function test_confirmation_failure_rolls_back_payment(): void
    {
        \Illuminate\Support\Facades\Event::listen('eloquent.creating: '.BookingStatusLog::class, fn () => throw new \RuntimeException('Simulated history failure'));
        try {
            $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Card'])->assertServerError();
        } finally {
            \Illuminate\Support\Facades\Event::forget('eloquent.creating: '.BookingStatusLog::class);
        }
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('Pending', $this->booking->fresh()->status);
    }

    public function test_staff_linked_payment_create_and_edit_ignore_forged_amounts(): void
    {
        $this->grantStaffPermissions('manage_payments');
        $this->actingAs(User::factory()->staff()->create());
        $payload = ['user_id' => $this->customer->id, 'booking_id' => $this->booking->id, 'amount' => 1,
            'payment_method' => 'Cash', 'payment_date' => today()->toDateString(), 'status' => 'Pending', 'reference_number' => 'STAFF-BOOKING'];
        $this->post(route('payments.store'), $payload)->assertSessionHasNoErrors();
        $payment = Payment::sole();
        $this->assertSame('274.50', $payment->amount);
        $this->put(route('payments.update', $payment), array_merge($payload, ['amount' => 2, 'status' => 'Paid']))->assertSessionHasNoErrors();
        $this->assertSame('274.50', $payment->fresh()->amount);
        $this->assertSame('Confirmed', $this->booking->fresh()->status);
        $this->post(route('payments.store'), array_merge($payload, ['reference_number' => 'DUPLICATE']))->assertSessionHasErrors('booking_id');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_cancelled_booking_cannot_be_confirmed_by_staff_payment_verification(): void
    {
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Cash at Hotel']);
        $payment = Payment::sole();
        $this->booking->update(['status' => 'Cancelled']);
        $this->actingAs(User::factory()->staff()->create())->post(route('payments.verify', $payment))->assertSessionHasErrors('payment');
        $this->assertSame('Pending', $payment->fresh()->status);
        $this->assertSame('Cancelled', $this->booking->fresh()->status);
    }

    public function test_card_payment_does_not_advance_checked_in_booking(): void
    {
        $this->booking->update(['status' => 'Checked In']);
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Card'])->assertSessionHasNoErrors();
        $this->assertSame('Checked In', $this->booking->fresh()->status);
        $this->assertDatabaseCount('booking_status_logs', 0);
    }

    public function test_forged_sensitive_fields_are_not_flashed_on_invalid_submission(): void
    {
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'invalid', 'card_number' => '4242424242424242', 'cvv' => '123'])
            ->assertSessionHasErrors('payment_method')->assertSessionMissing('_old_input.card_number')->assertSessionMissing('_old_input.cvv');
    }

    public function test_payment_booking_foreign_key_restricts_booking_deletion(): void
    {
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Cash at Hotel']);
        $keys = \Illuminate\Support\Facades\Schema::getForeignKeys('payments');
        $this->assertCount(1, array_filter($keys, fn ($key) => $key['columns'] === ['booking_id'] && $key['foreign_table'] === 'bookings'));
        foreach (['card_number', 'cvv', 'expiry_date'] as $field) {
            $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('payments', $field));
        }
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->booking->delete();
    }

    public function test_staff_cannot_delete_a_booking_with_a_payment(): void
    {
        $this->grantStaffPermissions('delete_bookings');
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Cash at Hotel']);
        $this->actingAs(User::factory()->staff()->create());
        $this->delete(route('bookings.destroy', $this->booking))->assertSessionHasErrors('booking');
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id]);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_booking_edits_keep_payment_ownership_and_paid_total_consistent(): void
    {
        $this->post(route('customer.payments.booking.store', $this->booking), ['payment_method' => 'Cash at Hotel']);
        $payment = Payment::sole();
        $this->actingAs(User::factory()->staff()->create());
        $payload = ['user_id' => $this->customer->id, 'room_id' => $this->booking->room_id,
            'check_in_date' => $this->booking->check_in_date, 'check_out_date' => $this->booking->check_out_date,
            'number_of_guests' => 2];
        $this->put(route('bookings.update', $this->booking), array_merge($payload, ['user_id' => User::factory()->create()->id]))->assertSessionHasErrors('user_id');
        $this->assertSame($this->customer->id, $this->booking->fresh()->user_id);
        $this->put(route('bookings.update', $this->booking), $payload)->assertSessionHasNoErrors();
        $this->assertEquals($this->booking->fresh()->total_amount, $payment->fresh()->amount);
        $this->post(route('payments.verify', $payment));
        $this->put(route('bookings.update', $this->booking), array_merge($payload, ['check_out_date' => today()->addDays(5)->toDateString()]))->assertSessionHasErrors('payment');
        $this->assertEquals($this->booking->fresh()->total_amount, $payment->fresh()->amount);
    }
}
