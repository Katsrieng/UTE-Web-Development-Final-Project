<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPaymentSlip;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingPaymentSlipTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $customer;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('payment-settings/test-qr.png', 'test QR');
        \App\Models\PaymentSetting::create(['account_name' => 'Utopia Bay Resort', 'khqr_image' => 'payment-settings/test-qr.png', 'aba_khqr_enabled' => true]);

        $this->customer = User::factory()->create();
        $roomType = RoomType::create([
            'name' => 'Deluxe Suite',
            'base_price' => 150.00,
            'capacity' => 2,
        ]);
        $this->room = Room::create([
            'room_type_id' => $roomType->id,
            'room_number' => 'QR-101',
            'price_per_night' => 150.00,
        ]);
    }

    public function test_qr_payment_section_is_hidden_for_pending_booking(): void
    {
        $booking = $this->createBooking($this->customer, 'Pending');

        $response = $this->actingAs($this->customer)
            ->get(route('customer.bookings.show', $booking));

        $response->assertOk();
        $response->assertDontSeeText('Pay by QR Code (Cambodia KHQR & ABA)');
        $response->assertDontSeeText('Submit Payment Slip');
    }

    public function test_qr_payment_section_is_accessible_from_checkout_for_confirmed_booking(): void
    {
        $booking = $this->createBooking($this->customer, 'Confirmed');

        $response = $this->actingAs($this->customer)
            ->get(route('customer.bookings.show', $booking));

        $response->assertOk();
        $response->assertDontSeeText('Pay by QR Code (Cambodia KHQR & ABA)');
        $response->assertSeeText('Pay Now');
        $this->get(route('customer.payments.booking', $booking))->assertOk()
            ->assertSeeText('Pay with ABA / KHQR')->assertSee('payment-settings/test-qr.png');
    }

    public function test_customer_can_upload_valid_payment_slip(): void
    {
        $booking = $this->createBooking($this->customer, 'Confirmed');
        $file = UploadedFile::fake()->image('my-khqr-slip.png', 600, 800)->size(500);

        $response = $this->actingAs($this->customer)
            ->post(route('customer.bookings.payment-slip.store', $booking), [
                'payment_slip' => $file,
            ]);

        $response->assertRedirect(route('customer.payments.booking', $booking));
        $response->assertSessionHas('success', 'Payment slip uploaded. Our staff will verify it shortly.');

        $this->assertDatabaseHas('booking_payment_slips', [
            'booking_id' => $booking->id,
            'original_filename' => 'my-khqr-slip.png',
            'reviewed' => false,
        ]);

        $slip = BookingPaymentSlip::where('booking_id', $booking->id)->first();
        $this->assertNotNull($slip);
        Storage::disk('public')->assertExists($slip->file_path);
    }

    public function test_customer_cannot_upload_invalid_file_type(): void
    {
        $booking = $this->createBooking($this->customer, 'Confirmed');
        $file = UploadedFile::fake()->create('receipt.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->customer)
            ->post(route('customer.bookings.payment-slip.store', $booking), [
                'payment_slip' => $file,
            ]);

        $response->assertSessionHasErrors(['payment_slip']);
        $this->assertDatabaseEmpty('booking_payment_slips');
    }

    public function test_customer_cannot_upload_oversized_file(): void
    {
        $booking = $this->createBooking($this->customer, 'Confirmed');
        // Max size is 5120 KB (5MB)
        $file = UploadedFile::fake()->image('large-slip.jpg')->size(6000);

        $response = $this->actingAs($this->customer)
            ->post(route('customer.bookings.payment-slip.store', $booking), [
                'payment_slip' => $file,
            ]);

        $response->assertSessionHasErrors(['payment_slip']);
        $this->assertDatabaseEmpty('booking_payment_slips');
    }

    public function test_customer_cannot_upload_slip_to_another_customer_booking(): void
    {
        $otherCustomer = User::factory()->create();
        $booking = $this->createBooking($otherCustomer, 'Confirmed');
        $file = UploadedFile::fake()->image('slip.png', 400, 400);

        $response = $this->actingAs($this->customer)
            ->post(route('customer.bookings.payment-slip.store', $booking), [
                'payment_slip' => $file,
            ]);

        $response->assertNotFound();
        $this->assertDatabaseEmpty('booking_payment_slips');
    }

    public function test_customer_can_replace_existing_payment_slip(): void
    {
        $booking = $this->createBooking($this->customer, 'Confirmed');

        $firstFile = UploadedFile::fake()->image('old-slip.png', 400, 400)->size(300);
        $this->actingAs($this->customer)
            ->post(route('customer.bookings.payment-slip.store', $booking), [
                'payment_slip' => $firstFile,
            ]);

        $firstSlip = BookingPaymentSlip::where('booking_id', $booking->id)->first();
        $firstPath = $firstSlip->file_path;
        Storage::disk('public')->assertExists($firstPath);

        // Upload replacement
        $secondFile = UploadedFile::fake()->image('new-aba-slip.jpg', 600, 600)->size(400);
        $response = $this->actingAs($this->customer)
            ->post(route('customer.bookings.payment-slip.store', $booking), [
                'payment_slip' => $secondFile,
            ]);

        $response->assertRedirect(route('customer.payments.booking', $booking));
        $this->assertDatabaseCount('booking_payment_slips', 1);
        $this->assertDatabaseHas('booking_payment_slips', [
            'booking_id' => $booking->id,
            'original_filename' => 'new-aba-slip.jpg',
        ]);

        // Old file deleted, new file exists
        Storage::disk('public')->assertMissing($firstPath);
        $newSlip = BookingPaymentSlip::where('booking_id', $booking->id)->first();
        Storage::disk('public')->assertExists($newSlip->file_path);
    }

    public function test_customer_can_delete_payment_slip(): void
    {
        $booking = $this->createBooking($this->customer, 'Confirmed');
        $file = UploadedFile::fake()->image('slip-to-delete.png');

        $this->actingAs($this->customer)
            ->post(route('customer.bookings.payment-slip.store', $booking), [
                'payment_slip' => $file,
            ]);

        $slip = BookingPaymentSlip::where('booking_id', $booking->id)->first();
        Storage::disk('public')->assertExists($slip->file_path);

        $response = $this->actingAs($this->customer)
            ->delete(route('customer.bookings.payment-slip.destroy', $booking));

        $response->assertRedirect(route('customer.payments.booking', $booking));
        $response->assertSessionHas('success', 'Payment slip removed. You can now upload a new one.');

        $this->assertDatabaseEmpty('booking_payment_slips');
        Storage::disk('public')->assertMissing($slip->file_path);
    }

    public function test_staff_can_view_uploaded_payment_slip_on_booking_details(): void
    {
        $this->grantStaffPermissions('manage_payments');
        $staff = User::factory()->staff()->create();
        $booking = $this->createBooking($this->customer, 'Confirmed');

        $slip = BookingPaymentSlip::create([
            'booking_id' => $booking->id,
            'file_path' => 'payment-slips/test-slip.png',
            'original_filename' => 'customer-receipt.png',
            'mime_type' => 'image/png',
            'reviewed' => false,
        ]);

        $response = $this->actingAs($staff)
            ->get(route('bookings.show', $booking));

        $response->assertOk();
        $response->assertSeeText('Payment Slip Uploaded:');
        $response->assertSeeText('customer-receipt.png');
        $response->assertSeeText('Needs Review');
        $response->assertSeeText('Record Payment');
        $response->assertSee(route('payments.create', [
            'user_id' => $booking->user_id,
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
            'payment_method' => 'ABA / KHQR',
        ]));
    }

    private function createBooking(User $customer, string $status): Booking
    {
        return Booking::create([
            'user_id' => $customer->id,
            'room_id' => $this->room->id,
            'check_in_date' => '2026-10-20',
            'check_out_date' => '2026-10-22',
            'number_of_guests' => 2,
            'total_amount' => 300.00,
            'status' => $status,
        ]);
    }
}
