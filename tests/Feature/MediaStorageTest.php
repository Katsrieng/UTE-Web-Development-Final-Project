<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\MediaStorage;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_local_drivers_keep_existing_paths_and_private_slip_route(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $media = app(MediaStorage::class);

        $image = $media->storePublic(UploadedFile::fake()->image('room.jpg'), 'rooms/gallery/5');
        $slip = $media->storeSlip(UploadedFile::fake()->image('slip.png'), 'booking-5-TEST.png');

        Storage::disk('public')->assertExists($image);
        Storage::disk('local')->assertExists($slip);
        $this->assertStringContainsString('/storage/'.$image, $media->publicUrl($image));
        $this->assertTrue($media->deletePublic($image));
        $this->assertTrue($media->deleteSlip($slip));
        Storage::disk('public')->assertMissing($image);
        Storage::disk('local')->assertMissing($slip);
    }

    public function test_cloudinary_public_upload_uses_selected_backend_and_deletes_owned_asset(): void
    {
        config()->set('media.public_driver', 'cloudinary');
        $cloud = Mockery::mock(Cloudinary::class);
        $upload = Mockery::mock(UploadApi::class);
        $cloud->shouldReceive('uploadApi')->twice()->andReturn($upload);
        $upload->shouldReceive('upload')->once()->withArgs(function ($source, $options) {
            $this->assertFileExists($source);
            $this->assertSame('upload', $options['type']);
            $this->assertStringStartsWith('utopia-bay/facilities/7/', $options['public_id']);

            return true;
        })->andReturnUsing(fn ($source, $options) => new ApiResponse([
            'public_id' => $options['public_id'], 'format' => 'jpg',
        ], []));
        $upload->shouldReceive('destroy')->once()->withArgs(fn ($id, $options) =>
            str_starts_with($id, 'utopia-bay/facilities/7/') && $options['type'] === 'upload'
        )->andReturn(new ApiResponse(['result' => 'ok'], []));
        $media = $this->withClient($cloud);

        $path = $media->storePublic(UploadedFile::fake()->image('facility.jpg'), 'facilities/7');
        $this->assertStringStartsWith('cloudinary:utopia-bay/facilities/7/', $path);
        $this->assertTrue($media->deletePublic($path));
    }

    public function test_cloudinary_public_url_is_secure_and_has_no_local_storage_prefix(): void
    {
        config()->set('media.cloudinary', ['cloud_name' => 'demo', 'api_key' => '123', 'api_secret' => 'abc']);

        $url = app(MediaStorage::class)->publicUrl('cloudinary:utopia-bay/rooms/gallery/5/test.jpg');

        $this->assertStringStartsWith('https://res.cloudinary.com/demo/image/upload/', $url);
        $this->assertStringContainsString('utopia-bay/rooms/gallery/5/test.jpg', $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_authenticated_slip_upload_and_short_lived_delivery_url(): void
    {
        config()->set('media.payment_slip_driver', 'cloudinary');
        config()->set('media.cloudinary', ['cloud_name' => 'demo', 'api_key' => '123', 'api_secret' => 'abc']);
        $cloud = Mockery::mock(Cloudinary::class);
        $upload = Mockery::mock(UploadApi::class);
        $cloud->shouldReceive('uploadApi')->times(3)->andReturn($upload);
        $upload->shouldReceive('upload')->once()->withArgs(function ($source, $options) {
            $this->assertSame('authenticated', $options['type']);
            $this->assertStringStartsWith('utopia-bay/payment-slips/', $options['public_id']);

            return true;
        })->andReturnUsing(fn ($source, $options) => new ApiResponse([
            'public_id' => $options['public_id'], 'format' => 'png',
        ], []));
        $upload->shouldReceive('privateDownloadUrl')->once()->withArgs(function ($id, $format, $options) {
            $this->assertSame('authenticated', $options['type']);
            $this->assertSame('png', $format);
            $this->assertLessThanOrEqual(now()->addMinutes(5)->timestamp, $options['expires_at']);

            return true;
        })->andReturn('https://api.cloudinary.com/signed-short-lived');
        $upload->shouldReceive('destroy')->once()->withArgs(fn ($id, $options) =>
            str_starts_with($id, 'utopia-bay/payment-slips/') && $options['type'] === 'authenticated'
        )->andReturn(new ApiResponse(['result' => 'ok'], []));
        $media = $this->withClient($cloud);

        $path = $media->storeSlip(UploadedFile::fake()->image('receipt.png'), 'ignored.png');
        $this->assertStringStartsWith('cloudinary:utopia-bay/payment-slips/', $path);
        $this->assertSame('https://api.cloudinary.com/signed-short-lived', $media->slipDownloadUrl($path));
        $this->assertTrue($media->deleteSlip($path));
    }

    public function test_cloudinary_slip_still_requires_booking_ownership_before_redirect(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $type = RoomType::create(['name' => 'Media test', 'base_price' => 100, 'capacity' => 2]);
        $room = Room::create(['room_type_id' => $type->id, 'room_number' => 'MEDIA-1', 'price_per_night' => 100]);
        $booking = Booking::create([
            'user_id' => $owner->id, 'room_id' => $room->id,
            'check_in_date' => now()->addDay()->toDateString(), 'check_out_date' => now()->addDays(2)->toDateString(),
            'number_of_guests' => 1, 'total_amount' => 100, 'status' => 'Pending',
        ]);
        $slip = $booking->paymentSlip()->create([
            'file_path' => 'cloudinary:utopia-bay/payment-slips/test.png',
            'original_filename' => 'receipt.png', 'mime_type' => 'image/png', 'reviewed' => false,
        ]);
        $media = Mockery::mock(MediaStorage::class);
        $media->shouldReceive('isCloudinary')->andReturn(true);
        $media->shouldReceive('slipExists')->andReturn(true, false);
        $media->shouldReceive('slipDownloadUrl')->andReturn('https://api.cloudinary.com/signed-short-lived');
        $this->app->instance(MediaStorage::class, $media);

        $url = route('booking-payment-slips.show', $slip);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($owner)->get($url)->assertRedirect('https://api.cloudinary.com/signed-short-lived');
        $this->actingAs($owner)->get($url)->assertNotFound();
    }

    private function withClient(Cloudinary $client): MediaStorage
    {
        $media = new MediaStorage;
        $property = new \ReflectionProperty(MediaStorage::class, 'cloudinary');
        $property->setValue($media, $client);

        return $media;
    }
}
