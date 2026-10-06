<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomImage;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomGalleryTest extends TestCase
{
    use RefreshDatabase;

    private function room(): Room
    {
        $type = RoomType::firstOrCreate(['name' => 'Gallery Suite'], ['base_price' => 80, 'capacity' => 2]);

        return Room::create(['room_type_id' => $type->id, 'room_number' => 'R'.Room::count(), 'floor' => 1, 'price_per_night' => 80, 'status' => 'available', 'image' => 'images/rooms/standard-single.jpg']);
    }

    private function upload(Room $room, int $count = 1)
    {
        return $this->post('/management/rooms/'.$room->id.'/images', ['images' => array_map(fn () => UploadedFile::fake()->image('photo.jpg'), range(1, $count))]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_multiple_upload_relationship_and_primary(): void
    {
        $room = $this->room();
        $this->upload($room, 5)->assertRedirect();
        $this->assertCount(5, $room->images);
        $this->assertSame($room->id, $room->images->first()->room->id);
        $this->assertSame(1, $room->images->where('is_primary', true)->count());
        foreach ($room->images as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }
        $this->assertSame('images/rooms/standard-single.jpg', $room->fresh()->image);
    }

    public function test_upload_limits_and_invalid_files_are_atomic(): void
    {
        $room = $this->room();
        $this->upload($room, 6)->assertSessionHasErrors('images');
        $this->post('/management/rooms/'.$room->id.'/images', ['images' => [UploadedFile::fake()->image('good.jpg'), UploadedFile::fake()->create('bad.txt')]])->assertSessionHasErrors('images.1');
        $this->assertSame(0, $room->images()->count());
        $this->assertCount(0, Storage::disk('public')->allFiles());
        $this->upload($room, 5)->assertRedirect();
        $this->upload($room, 3)->assertRedirect();
        $this->upload($room, 3)->assertSessionHasErrors('images');
        $this->upload($room, 2)->assertRedirect();
        $this->upload($room)->assertSessionHasErrors('images');
        $this->assertSame(10, $room->images()->count());
    }

    public function test_cover_ordering_and_individual_deletion(): void
    {
        $room = $this->room();
        $this->upload($room, 3);
        [$first, $second, $third] = $room->images()->get()->all();
        $base = '/management/rooms/'.$room->id.'/images/';
        $this->patch($base.$second->id.'/primary')->assertRedirect();
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertFalse($first->fresh()->is_primary);
        $this->patch($base.$third->id, ['alt_text' => 'Ocean view', 'sort_order' => 0])->assertRedirect();
        $this->patch($base.$first->id, ['alt_text' => null, 'sort_order' => 9])->assertRedirect();
        $this->assertSame('Ocean view', $third->fresh()->alt_text);
        $this->delete($base.$second->id)->assertRedirect();
        Storage::disk('public')->assertMissing($second->image_path);
        Storage::disk('public')->assertExists($first->image_path);
        Storage::disk('public')->assertExists($third->image_path);
        $this->assertTrue($third->fresh()->is_primary);
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertSame(2, $room->images()->count());
    }

    public function test_public_gallery_fallback_and_reservation_link(): void
    {
        $room = $this->room();
        $this->get(route('rooms.show', $room))->assertOk()->assertSee(asset($room->image), false);
        $this->upload($room, 2);
        $images = $room->images()->get();
        $this->actingAs(User::factory()->create());
        $response = $this->get(route('rooms.show', $room));
        $response->assertOk()->assertSee('data-room-thumbnail', false)->assertDontSee(asset($room->image), false)->assertSee(route('customer.bookings.create', $room), false);
        foreach ($images as $image) {
            $response->assertSee($image->url(), false);
        }
        $this->get(route('rooms.index'))->assertOk()->assertSee($images->first()->url(), false);
        $this->get(route('customer.bookings.create', $room))->assertOk();
        $room->images()->update(['is_primary' => false]);
        $this->assertSame($images->first()->url(), $room->fresh()->coverImageUrl());
        $this->actingAs(User::factory()->admin()->create());
        foreach ($images as $image) {
            $this->delete('/management/rooms/'.$room->id.'/images/'.$image->id)->assertRedirect();
        }
        $this->get(route('rooms.show', $room))->assertSee(asset($room->image), false);
        $room->update(['image' => null]);
        $this->get(route('rooms.show', $room))->assertOk()->assertSee('detail-placeholder', false);
    }

    public function test_authorization_and_cross_room_protection(): void
    {
        $room = $this->room();
        $other = $this->room();
        $this->upload($room);
        $image = $room->images()->first();
        foreach (['', '/primary'] as $suffix) {
            $this->patch('/management/rooms/'.$other->id.'/images/'.$image->id.$suffix, ['sort_order' => 0])->assertNotFound();
        }
        $this->delete('/management/rooms/'.$other->id.'/images/'.$image->id)->assertNotFound();
        $this->actingAs(User::factory()->create());
        $this->upload($room)->assertForbidden();
        $this->patch('/management/rooms/'.$room->id.'/images/'.$image->id.'/primary')->assertForbidden();
        $this->delete('/management/rooms/'.$room->id.'/images/'.$image->id)->assertForbidden();
        auth()->logout();
        $this->upload($room)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->staff()->create());
        $this->upload($room)->assertRedirect();
    }

    public function test_room_crud_and_gallery_cleanup_preserve_legacy_files(): void
    {
        $room = $this->room();
        Storage::disk('public')->put('rooms/legacy.jpg', 'legacy');
        $room->update(['image' => 'rooms/legacy.jpg']);
        $payload = $room->only(['room_type_id', 'room_number', 'floor', 'price_per_night', 'status']);
        $payload['images'] = [UploadedFile::fake()->image('new.jpg')];
        $this->put(route('management.rooms.update', $room), $payload)->assertRedirect();
        $image = $room->images()->first();
        $this->get(route('management.rooms.edit', $room))->assertOk();
        $this->get(route('management.rooms.show', $room))->assertOk();
        $this->delete(route('management.rooms.destroy', $room))->assertRedirect();
        $this->assertDatabaseMissing('room_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->image_path);
        Storage::disk('public')->assertExists('rooms/legacy.jpg');
        $payload['room_number'] = 'NEW';
        $payload['images'] = [UploadedFile::fake()->image('create.jpg')];
        $this->post(route('management.rooms.store'), $payload)->assertRedirect();
        $this->assertSame(1, Room::where('room_number', 'NEW')->first()->images()->count());
    }

    public function test_room_type_cascade_cleans_gallery(): void
    {
        $room = $this->room();
        $this->upload($room);
        $path = $room->images()->first()->image_path;
        $this->delete(route('management.room-types.destroy', $room->roomType))->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_failed_room_delete_keeps_gallery_files_and_records(): void
    {
        $room = $this->room();
        $this->upload($room);
        $image = $room->images()->first();
        Event::listen('eloquent.deleting: '.Room::class, function () {
            throw new \RuntimeException('Simulated database failure');
        });
        try {
            $this->delete(route('management.rooms.destroy', $room))->assertServerError();
        } finally {
            Event::forget('eloquent.deleting: '.Room::class);
        }
        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseHas('room_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($image->image_path);
    }

    public function test_shared_files_and_defaults_are_not_removed(): void
    {
        $room = $this->room();
        $other = $this->room();
        $this->upload($room);
        $image = $room->images()->first();
        $other->images()->create(['image_path' => $image->image_path]);
        $this->delete('/management/rooms/'.$room->id.'/images/'.$image->id)->assertRedirect();
        Storage::disk('public')->assertExists($image->image_path);
        Storage::disk('public')->put('images/default.jpg', 'default');
        $default = $room->images()->create(['image_path' => 'images/default.jpg']);
        $this->delete('/management/rooms/'.$room->id.'/images/'.$default->id)->assertRedirect();
        Storage::disk('public')->assertExists('images/default.jpg');
    }

    public function test_failed_image_record_save_cleans_new_upload(): void
    {
        $room = $this->room();
        Event::listen('eloquent.creating: '.RoomImage::class, function () {
            throw new \RuntimeException('Simulated image save failure');
        });
        try {
            $this->upload($room)->assertServerError();
        } finally {
            Event::forget('eloquent.creating: '.RoomImage::class);
        }
        $this->assertSame(0, $room->images()->count());
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_edit_limit_failure_rolls_back_room_changes_and_combined_legacy_input(): void
    {
        $room = $this->room();
        $this->upload($room, 5);
        $this->upload($room, 5);
        $payload = $room->only(['room_type_id', 'room_number', 'floor', 'price_per_night', 'status']);
        $payload['price_per_night'] = 999;
        $payload['image'] = UploadedFile::fake()->image('old-input.jpg');
        $this->put(route('management.rooms.update', $room), $payload)->assertSessionHasErrors('images');
        $this->assertEquals(80, $room->fresh()->price_per_night);
        $empty = $this->room();
        $payload['room_number'] = $empty->room_number;
        $payload['images'] = array_map(fn () => UploadedFile::fake()->image('new.jpg'), range(1, 5));
        $this->put(route('management.rooms.update', $empty), $payload)->assertSessionHasErrors('images');
        $this->assertSame(0, $empty->images()->count());
    }

    public function test_image_size_metadata_validation_and_management_controls(): void
    {
        $room = $this->room();
        $this->post('/management/rooms/'.$room->id.'/images', ['images' => [UploadedFile::fake()->image('large.jpg')->size(2049)]])->assertSessionHasErrors('images.0');
        $this->upload($room, 2);
        $image = $room->images()->first();
        $this->patch('/management/rooms/'.$room->id.'/images/'.$image->id, ['alt_text' => str_repeat('x', 192), 'sort_order' => -1])->assertSessionHasErrors(['alt_text', 'sort_order']);
        $this->get(route('management.rooms.edit', $room))->assertOk()->assertSee('Use as cover')->assertSee('Delete photo')->assertSee('Save photo details');
        $room->images()->first()->update(['alt_text' => '<script>alert(1)</script>']);
        $this->get(route('rooms.show', $room))->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_alt_text_and_generated_orders_stay_within_metadata_limits(): void
    {
        $room = $this->room();
        $this->upload($room);
        $image = $room->images()->first();
        $this->patch('/management/rooms/'.$room->id.'/images/'.$image->id, ['alt_text' => str_repeat('x', 191), 'sort_order' => 9999])->assertSessionHasNoErrors()->assertRedirect();
        $this->upload($room, 3)->assertSessionHasNoErrors()->assertRedirect();
        foreach ($room->images()->get() as $photo) {
            $this->assertLessThanOrEqual(9999, $photo->sort_order);
            $this->patch('/management/rooms/'.$room->id.'/images/'.$photo->id, ['alt_text' => $photo->alt_text, 'sort_order' => $photo->sort_order])->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->get(route('management.rooms.edit', $room))->assertSee('maxlength="191"', false);
    }

    public function test_cleanup_failure_preserves_original_error_and_attempts_remaining_files(): void
    {
        $room = $this->room();
        $disk = Storage::disk('public');
        $proxy = \Mockery::mock($disk);
        $deletions = 0;
        $proxy->shouldReceive('delete')->andReturnUsing(function ($path) use ($disk, &$deletions) {
            if (++$deletions === 1) {
                throw new \RuntimeException('Cleanup failed');
            }

            return $disk->delete($path);
        });
        Storage::shouldReceive('disk')->with('public')->andReturn($proxy);
        $creations = 0;
        Event::listen('eloquent.creating: '.RoomImage::class, function () use (&$creations) {
            if (++$creations === 2) {
                throw new \RuntimeException('Original save failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->upload($room, 2);
            $this->fail('Expected image save failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Original save failure', $exception->getMessage());
        } finally {
            Event::forget('eloquent.creating: '.RoomImage::class);
        }
        $this->assertSame(0, $room->images()->count());
        $this->assertCount(1, $disk->allFiles());
    }
}
