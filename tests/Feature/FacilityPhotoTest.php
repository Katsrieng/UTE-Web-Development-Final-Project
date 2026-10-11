<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacilityPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_upload_is_saved_and_shown_in_staff_and_customer_views(): void
    {
        $this->post(route('management.facilities.store'), $this->details([
            'image' => UploadedFile::fake()->image('pool.jpg'),
        ]))->assertRedirect(route('management.facilities.index'));

        $facility = Facility::sole();
        $facility->update([
            'location' => 'Poolside',
            'opening_time' => '07:00:00',
            'closing_time' => '21:00:00',
            'price' => 12,
        ]);
        $this->assertStringStartsWith('facilities/'.$facility->id.'/', $facility->image);
        Storage::disk('public')->assertExists($facility->image);
        $url = $facility->imageUrl();
        $this->get(route('management.facilities.index'))->assertOk()->assertSee($url, false);
        $this->get(route('management.facilities.edit', $facility))->assertOk()->assertSee($url, false);
        $this->get(route('management.facilities.show', $facility))->assertOk()->assertSee($url, false);
        $this->get(route('facilities.index'))->assertOk()->assertSee($url, false)->assertSee('Learn more');
        $this->get(route('facilities.show', $facility))->assertOk()->assertSee($url, false)
            ->assertSee('Poolside')->assertSee('7:00 AM')->assertSee('9:00 PM')->assertSee('$12.00');
    }

    public function test_replacement_retention_validation_and_deletion_clean_up_owned_files(): void
    {
        $this->post(route('management.facilities.store'), $this->details([
            'image' => UploadedFile::fake()->image('first.png'),
        ]))->assertRedirect();
        $facility = Facility::sole();
        $first = $facility->image;

        $this->put(route('management.facilities.update', $facility), $this->details(['name' => 'Updated Pool']))->assertRedirect();
        $this->assertSame($first, $facility->fresh()->image);
        $this->put(route('management.facilities.update', $facility), $this->details([
            'image' => UploadedFile::fake()->create('not-an-image.txt'),
        ]))->assertSessionHasErrors('image');
        $this->assertSame($first, $facility->fresh()->image);
        $this->post(route('management.facilities.store'), $this->details([
            'image' => UploadedFile::fake()->image('too-large.jpg')->size(2049),
        ]))->assertSessionHasErrors('image');
        $this->assertDatabaseCount('facilities', 1);

        $this->put(route('management.facilities.update', $facility), $this->details([
            'image' => UploadedFile::fake()->image('replacement.webp'),
        ]))->assertRedirect();
        $second = $facility->fresh()->image;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->delete(route('management.facilities.destroy', $facility))->assertRedirect();
        Storage::disk('public')->assertMissing($second);
        $this->assertDatabaseCount('facilities', 0);
    }

    public function test_missing_image_placeholder_and_shared_or_legacy_files_are_preserved(): void
    {
        $empty = Facility::create($this->details());
        $this->get(route('facilities.index'))->assertOk()->assertSee('facility-photo-placeholder', false);
        $this->get(route('facilities.show', $empty))->assertOk()->assertSee('facility-photo-placeholder', false);

        $legacy = Facility::create($this->details(['name' => 'Legacy Facility', 'image' => 'images/facilities/default.jpg']));
        Storage::disk('public')->put('images/facilities/default.jpg', 'shared default');
        $this->delete(route('management.facilities.destroy', $legacy))->assertRedirect();
        Storage::disk('public')->assertExists('images/facilities/default.jpg');

        $this->post(route('management.facilities.store'), $this->details([
            'image' => UploadedFile::fake()->image('shared.jpg'),
        ]))->assertRedirect();
        $owner = Facility::whereNotNull('image')->sole();
        $sharedPath = $owner->image;
        Facility::create($this->details(['name' => 'Shared Facility', 'image' => $sharedPath]));
        $this->delete(route('management.facilities.destroy', $owner))->assertRedirect();
        Storage::disk('public')->assertExists($sharedPath);
    }

    private function details(array $overrides = []): array
    {
        return array_merge(['name' => 'Infinity Pool', 'description' => 'A relaxing pool.', 'status' => 'open'], $overrides);
    }
}
