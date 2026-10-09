<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PaymentSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_qr_removal_falls_back_to_demo_without_disabling_payments(): void
    {
        $this->grantStaffPermissions('manage_payment_settings');
        Storage::fake('public');
        Storage::disk('public')->put('payment-settings/custom.png', 'custom QR');
        $settings = PaymentSetting::create(['account_name' => 'Hotel Account', 'khqr_image' => 'payment-settings/custom.png', 'aba_khqr_enabled' => true]);
        $this->assertTrue($settings->khqrAvailable());
        $this->assertStringContainsString('payment-settings/custom.png', $settings->qrUrl());
        $this->actingAs(User::factory()->create())->delete(route('payment-settings.remove-qr'))->assertForbidden();
        Storage::disk('public')->assertExists('payment-settings/custom.png');
        $this->actingAs(User::factory()->staff()->create())->get(route('payment-settings.edit'))->assertOk()->assertSee('Custom hotel QR')->assertSee('Remove Custom QR');
        $this->delete(route('payment-settings.remove-qr'))->assertRedirect(route('payment-settings.edit'));
        $settings->refresh();
        $this->assertNull($settings->khqr_image);
        $this->assertTrue($settings->aba_khqr_enabled);
        $this->assertTrue($settings->khqrAvailable());
        $this->assertSame(asset('images/qr-khqr.png'), $settings->qrUrl());
        Storage::disk('public')->assertMissing('payment-settings/custom.png');
        $this->assertFileExists(public_path('images/qr-khqr.png'));
        $this->get(route('payment-settings.edit'))->assertOk()->assertSee('Default demo QR')->assertSee('images/qr-khqr.png')->assertDontSee('Remove Custom QR');
        $settings->update(['aba_khqr_enabled' => false]);
        $this->assertFalse($settings->khqrAvailable());
        $this->assertSame(asset('images/qr-khqr.png'), $settings->qrUrl());
    }

    public function test_management_can_update_single_settings_record_and_replace_qr_safely(): void
    {
        $this->grantStaffPermissions('manage_payment_settings');
        Storage::fake('public');
        $customer = User::factory()->create();
        $this->actingAs($customer)->get(route('payment-settings.edit'))->assertForbidden();
        $this->put(route('payment-settings.update'), ['account_name' => 'Forged', 'aba_khqr_enabled' => 1])->assertForbidden();
        $this->assertDatabaseCount('payment_settings', 0);
        $this->actingAs(User::factory()->staff()->create())->get(route('payment-settings.edit'))->assertOk();
        $this->put(route('payment-settings.update'), ['account_name' => 'Resort Account', 'account_label' => 'ABA account 123',
            'aba_khqr_enabled' => 1, 'khqr_image' => UploadedFile::fake()->image('qr.png')])->assertRedirect(route('payment-settings.edit'));
        $settings = PaymentSetting::current();
        $oldPath = $settings->khqr_image;
        $this->assertTrue($settings->khqrAvailable());
        Storage::disk('public')->assertExists($oldPath);
        $this->put(route('payment-settings.update'), ['account_name' => 'Resort Account', 'aba_khqr_enabled' => 1,
            'khqr_image' => UploadedFile::fake()->create('bad.pdf', 30, 'application/pdf')])->assertSessionHasErrors('khqr_image');
        $this->put(route('payment-settings.update'), ['account_name' => 'Resort Account', 'aba_khqr_enabled' => 1,
            'khqr_image' => UploadedFile::fake()->image('large.png')->size(2049)])->assertSessionHasErrors('khqr_image');
        $this->assertSame($oldPath, PaymentSetting::current()->khqr_image);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->put(route('payment-settings.update'), ['account_name' => 'Updated Account', 'aba_khqr_enabled' => 0,
            'khqr_image' => UploadedFile::fake()->image('replacement.jpg')])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payment_settings', 1);
        $this->assertNotSame($oldPath, PaymentSetting::current()->khqr_image);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists(PaymentSetting::current()->khqr_image);
        $this->assertFalse(PaymentSetting::current()->khqrAvailable());
    }

    public function test_checkout_uses_managed_qr_and_handles_missing_or_disabled_settings(): void
    {
        Storage::fake('public');
        $customer = User::factory()->create();
        $type = RoomType::create(['name' => 'Suite', 'capacity' => 2, 'base_price' => 75]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => 'SETTINGS-101', 'floor' => 1, 'price_per_night' => 75, 'status' => 'available']);
        $booking = Booking::create(['user_id' => $customer->id, 'room_id' => $room->id, 'check_in_date' => today()->addDay(),
            'check_out_date' => today()->addDays(3), 'number_of_guests' => 2, 'total_amount' => 150, 'status' => 'Pending']);
        $this->actingAs($customer);
        $url = route('customer.payments.booking', $booking);
        $this->get($url)->assertOk()->assertSee('ABA/KHQR payment is temporarily unavailable')->assertDontSee('images/qr-aba.png')->assertDontSee('images/qr-khqr.png');
        $this->post(route('customer.payments.booking.store', $booking), ['payment_method' => 'ABA / KHQR',
            'payment_slip' => UploadedFile::fake()->image('slip.png')])->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('payments', 0);
        Storage::disk('public')->put('payment-settings/managed.png', 'test-image');
        $settings = PaymentSetting::create(['account_name' => 'Hotel Managed Account', 'account_label' => 'Reference 456',
            'khqr_image' => 'payment-settings/managed.png', 'aba_khqr_enabled' => true]);
        $this->get($url)->assertOk()->assertSee('Hotel Managed Account')->assertSee('Reference 456')->assertSee('payment-settings/managed.png')
            ->assertSee('$150.00')->assertSee('Booking #'.$booking->id)->assertDontSee('ABA/KHQR payment is temporarily unavailable');
        $settings->update(['aba_khqr_enabled' => false]);
        $this->get($url)->assertOk()->assertSee('ABA/KHQR payment is temporarily unavailable')->assertDontSee('payment-settings/managed.png');
        $settings->update(['aba_khqr_enabled' => true]);
        Storage::disk('public')->delete('payment-settings/managed.png');
        $this->get($url)->assertOk()->assertDontSee('ABA/KHQR payment is temporarily unavailable')->assertSee('images/qr-khqr.png')->assertDontSee('payment-settings/managed.png');
    }
}
