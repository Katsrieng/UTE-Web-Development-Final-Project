<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Facility;
use App\Models\PaymentSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Venue;
use App\Services\MediaStorage;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MigrateMediaToCloudinaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_dry_run_inspects_files_without_uploading_or_changing_database(): void
    {
        $facility = $this->facility('facilities/1/photo.jpg');
        Storage::disk('public')->put($facility->image, 'image bytes');
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldNotReceive('migratePublicFile');
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary', ['--dry-run' => true]));
        $this->assertStringContainsString('Would migrate (dry run): 1', Artisan::output());
        $this->assertSame('facilities/1/photo.jpg', $facility->fresh()->image);
        Storage::disk('public')->assertExists($facility->image);
    }

    public function test_successful_public_migration_updates_only_path_and_rerun_skips_it(): void
    {
        $room = $this->room();
        $image = $room->images()->create(['image_path' => 'rooms/gallery/'.$room->id.'/photo.jpg', 'sort_order' => 3, 'is_primary' => true, 'alt_text' => 'Ocean view']);
        Storage::disk('public')->put($image->image_path, 'image bytes');
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('migratePublicFile')->once()->withArgs(fn ($absolute, $folder) =>
            is_file($absolute) && $folder === 'rooms/gallery/'.$room->id
        )->andReturn('cloudinary:utopia-bay/rooms/gallery/'.$room->id.'/new-id.jpg');
        $media->shouldReceive('isCloudinary')->once()->andReturn(true);
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary'));
        $this->assertStringContainsString('Migrated: 1', Artisan::output());
        $this->assertSame('cloudinary:utopia-bay/rooms/gallery/'.$room->id.'/new-id.jpg', $image->fresh()->image_path);
        $this->assertSame(3, $image->fresh()->sort_order);
        $this->assertTrue($image->fresh()->is_primary);
        $this->assertSame('Ocean view', $image->fresh()->alt_text);
        Storage::disk('public')->assertExists('rooms/gallery/'.$room->id.'/photo.jpg');

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary'));
        $this->assertStringContainsString('Already Cloudinary / skipped: 1', Artisan::output());
    }

    public function test_authenticated_payment_slip_migration_preserves_metadata(): void
    {
        $booking = $this->booking();
        $path = 'booking-payment-slips/booking-'.$booking->id.'-TEST123.png';
        $slip = $booking->paymentSlip()->create(['file_path' => $path, 'original_filename' => 'bank-receipt.png', 'mime_type' => 'image/png', 'reviewed' => true]);
        Storage::disk('local')->put($path, 'private bytes');
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('migrateSlipFile')->once()->withArgs(fn ($absolute) => is_file($absolute))
            ->andReturn('cloudinary:utopia-bay/payment-slips/new-id.png');
        $media->shouldReceive('isCloudinary')->once()->andReturn(true);
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary'));
        $this->assertSame('cloudinary:utopia-bay/payment-slips/new-id.png', $slip->fresh()->file_path);
        $this->assertSame('bank-receipt.png', $slip->fresh()->original_filename);
        $this->assertSame('image/png', $slip->fresh()->mime_type);
        $this->assertTrue($slip->fresh()->reviewed);
        Storage::disk('local')->assertExists($path);
    }

    public function test_missing_file_is_reported_and_database_value_remains_unchanged(): void
    {
        $facility = $this->facility('facilities/1/missing.jpg');

        $this->assertSame(1, Artisan::call('media:migrate-to-cloudinary'));
        $this->assertStringContainsString('Missing local files: 1', Artisan::output());
        $this->assertSame('facilities/1/missing.jpg', $facility->fresh()->image);
    }

    public function test_upload_failure_keeps_database_and_source_untouched(): void
    {
        $facility = $this->facility('facilities/1/photo.jpg');
        Storage::disk('public')->put($facility->image, 'image bytes');
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('migratePublicFile')->once()->andThrow(new \RuntimeException('simulated upload failure'));
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(1, Artisan::call('media:migrate-to-cloudinary', ['--delete-local' => true]));
        $this->assertStringContainsString('Failed uploads or updates: 1', Artisan::output());
        $this->assertSame('facilities/1/photo.jpg', $facility->fresh()->image);
        Storage::disk('public')->assertExists($facility->image);
    }

    public function test_remaining_public_media_types_and_legacy_room_type_path_are_migrated(): void
    {
        $room = $this->room();
        $room->update(['image' => 'rooms/legacy.jpg']);
        $room->roomType->update(['image' => '/storage/room_types/type.jpg']);
        $venue = Venue::create(['name' => 'Beach Pavilion', 'location' => 'Beach', 'capacity' => 20, 'event_types' => ['wedding'], 'images' => ['venues/main.jpg']]);
        $setting = PaymentSetting::create(['account_name' => 'Utopia Bay', 'khqr_image' => 'payment-settings/qr.png']);
        foreach (['rooms/legacy.jpg', 'room_types/type.jpg', 'venues/main.jpg', 'payment-settings/qr.png'] as $path) {
            Storage::disk('public')->put($path, 'image bytes');
        }
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('migratePublicFile')->times(4)->andReturnUsing(fn ($absolute, $folder) => 'cloudinary:utopia-bay/'.$folder.'/new-id.jpg');
        $media->shouldReceive('isCloudinary')->times(4)->andReturn(true);
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary'));
        $this->assertSame('cloudinary:utopia-bay/rooms/new-id.jpg', $room->fresh()->image);
        $this->assertSame('cloudinary:utopia-bay/room_types/new-id.jpg', $room->roomType->fresh()->image);
        $this->assertSame(['cloudinary:utopia-bay/venues/new-id.jpg'], $venue->fresh()->images);
        $this->assertSame('cloudinary:utopia-bay/payment-settings/new-id.jpg', $setting->fresh()->khqr_image);
    }

    public function test_delete_local_removes_only_successfully_migrated_unreferenced_source(): void
    {
        $facility = $this->facility('facilities/1/photo.jpg');
        Storage::disk('public')->put($facility->image, 'image bytes');
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('migratePublicFile')->once()->andReturn('cloudinary:utopia-bay/facilities/1/new-id.jpg');
        $media->shouldReceive('isCloudinary')->once()->andReturn(true);
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary', ['--delete-local' => true]));
        $this->assertStringContainsString('Unreferenced local files deleted: 1', Artisan::output());
        Storage::disk('public')->assertMissing('facilities/1/photo.jpg');
    }

    public function test_delete_local_keeps_a_source_still_referenced_by_another_record(): void
    {
        $facility = $this->facility('facilities/1/photo.jpg');
        Facility::create(['name' => 'Shared reference', 'image' => $facility->image]);
        Storage::disk('public')->put($facility->image, 'image bytes');
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('migratePublicFile')->once()->andReturn('cloudinary:utopia-bay/facilities/1/new-id.jpg');
        $media->shouldReceive('isCloudinary')->once()->andReturn(true);
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary', ['--delete-local' => true]));
        Storage::disk('public')->assertExists('facilities/1/photo.jpg');
        $this->assertStringContainsString('Unreferenced local files deleted: 0', Artisan::output());
    }

    public function test_legacy_public_slip_is_uploaded_as_authenticated_and_source_is_retained(): void
    {
        $booking = $this->booking();
        $old = 'payment-slips/booking-'.$booking->id.'-TEST123.png';
        $slip = $booking->paymentSlip()->create(['file_path' => $old, 'original_filename' => 'receipt.png', 'mime_type' => 'image/png', 'reviewed' => false]);
        Storage::disk('public')->put($old, 'old receipt');
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('migrateSlipFile')->once()->andReturn('cloudinary:utopia-bay/payment-slips/new-id.png');
        $media->shouldReceive('isCloudinary')->once()->andReturn(true);
        $this->app->instance(MediaStorage::class, $media);

        $this->assertSame(0, Artisan::call('media:migrate-to-cloudinary'));
        $this->assertSame('cloudinary:utopia-bay/payment-slips/new-id.png', $slip->fresh()->file_path);
        Storage::disk('public')->assertExists($old);
    }

    private function facility(string $path): Facility
    {
        return Facility::create(['name' => 'Test Pool', 'image' => $path]);
    }

    private function room(): Room
    {
        $type = RoomType::create(['name' => 'Test Suite', 'base_price' => 100, 'capacity' => 2]);

        return Room::create(['room_type_id' => $type->id, 'room_number' => 'MIG-101', 'price_per_night' => 100]);
    }

    private function booking(): Booking
    {
        $room = $this->room();
        $user = User::factory()->create();

        return Booking::create([
            'user_id' => $user->id, 'room_id' => $room->id,
            'check_in_date' => now()->addDay()->toDateString(), 'check_out_date' => now()->addDays(2)->toDateString(),
            'number_of_guests' => 1, 'total_amount' => 100, 'status' => 'Pending',
        ]);
    }
}
