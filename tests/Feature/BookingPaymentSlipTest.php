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
        \Illuminate\Support\Facades\Storage::fake('local');
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
        Storage::disk('local')->assertExists($slip->file_path);
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
        Storage::disk('local')->assertExists($firstPath);

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
        Storage::disk('local')->assertMissing($firstPath);
        $newSlip = BookingPaymentSlip::where('booking_id', $booking->id)->first();
        Storage::disk('local')->assertExists($newSlip->file_path);
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
        Storage::disk('local')->assertExists($slip->file_path);

        $response = $this->actingAs($this->customer)
            ->delete(route('customer.bookings.payment-slip.destroy', $booking));

        $response->assertRedirect(route('customer.payments.booking', $booking));
        $response->assertSessionHas('success', 'Payment slip removed. You can now upload a new one.');

        $this->assertDatabaseEmpty('booking_payment_slips');
        Storage::disk('local')->assertMissing($slip->file_path);
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

    public function test_private_slip_route_requires_owner_or_staff_view_permission(): void
    {
        $booking = $this->createBooking($this->customer, 'Pending');
        $path = 'booking-payment-slips/booking-'.$booking->id.'-TEST123.png';
        Storage::disk('local')->put($path, 'private image');
        $slip = $booking->paymentSlip()->create([
            'file_path' => $path, 'original_filename' => 'receipt.png',
            'mime_type' => 'image/png', 'reviewed' => false,
        ]);
        $url = route('booking-payment-slips.show', $slip);

        $this->assertNotSame(200, $this->get('/storage/'.$path)->getStatusCode());
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->customer)->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->staff()->create())->get($url)->assertOk();
        \App\Models\Role::where('slug', 'staff')->firstOrFail()->permissions()->detach();
        $this->get($url)->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
        $this->get('/booking-payment-slips/999999')->assertNotFound();

        Storage::disk('local')->delete($path);
        $this->get($url)->assertNotFound();
        $slip->update(['file_path' => '../outside.png']);
        $this->get($url)->assertNotFound();
    }

    public function test_customer_and_staff_pages_use_protected_slip_url(): void
    {
        $booking = $this->createBooking($this->customer, 'Pending');
        $slip = $booking->paymentSlip()->create([
            'file_path' => 'booking-payment-slips/booking-'.$booking->id.'-TEST123.png',
            'original_filename' => 'receipt.png', 'mime_type' => 'image/png', 'reviewed' => false,
        ]);
        $url = route('booking-payment-slips.show', $slip);
        $this->actingAs($this->customer)->get(route('customer.payments.booking', $booking))
            ->assertOk()->assertSee($url, false)->assertDontSee('/storage/payment-slips/', false);
        $this->actingAs(User::factory()->staff()->create())->get(route('bookings.show', $booking))
            ->assertOk()->assertSee($url, false)->assertDontSee('/storage/payment-slips/', false);
    }

    public function test_legacy_public_slip_can_be_moved_to_private_storage(): void
    {
        $booking = $this->createBooking($this->customer, 'Pending');
        $old = 'payment-slips/booking-'.$booking->id.'-TEST123.png';
        Storage::disk('public')->put($old, 'legacy image');
        $slip = $booking->paymentSlip()->create([
            'file_path' => $old, 'original_filename' => 'receipt.png',
            'mime_type' => 'image/png', 'reviewed' => false,
        ]);
        $this->actingAs($this->customer)->get(route('booking-payment-slips.show', $slip))->assertOk();
        $this->artisan('payment-slips:privatize')->assertExitCode(0);
        Storage::disk('public')->assertMissing($old);
        $new = $slip->fresh()->file_path;
        $this->assertStringStartsWith('booking-payment-slips/', $new);
        Storage::disk('local')->assertExists($new);
        $this->get(route('booking-payment-slips.show', $slip))->assertOk();
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
