<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Product;
use App\Models\Variant;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageVariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_uploads_get_webp_variants_and_a_srcset(): void
    {
        $disk = config('commerce.media_disk', 'public');
        Storage::fake($disk);
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('saree.jpg', 1200, 1500), 'Red sari');
        $files = $media->variants['files'];
        $this->assertSame([480, 960], array_keys($files));
        foreach ($files as $path) {
            Storage::disk($disk)->assertExists($path);
            $this->assertStringEndsWith('.webp', $path);
            $this->assertStringStartsWith('RIFF', Storage::disk($disk)->get($path));
        }
        $srcset = $media->fresh()->srcset();
        $this->assertStringContainsString(' 480w', $srcset);
        $this->assertStringContainsString(' 960w', $srcset);
        $this->assertStringContainsString(' 1200w', $srcset);
        $this->assertSame(1200, $media->variants['width']);
        Storage::disk($disk)->assertExists($media->path);
    }

    public function test_small_images_keep_only_the_original(): void
    {
        Storage::fake(config('commerce.media_disk', 'public'));
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('tiny.jpg', 300, 300), 'Tiny');
        $this->assertNull($media->variants);
        $this->assertNull($media->srcset());
    }

    public function test_only_sizes_smaller_than_the_original_are_generated(): void
    {
        $disk = config('commerce.media_disk', 'public');
        Storage::fake($disk);
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('mid.jpg', 700, 900), 'Mid');
        $this->assertSame([480], array_keys($media->variants['files']));
    }

    public function test_deleting_media_removes_the_variants_too(): void
    {
        $disk = config('commerce.media_disk', 'public');
        Storage::fake($disk);
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('gone.jpg', 1200, 1500), 'Gone');
        $paths = [$media->path, ...array_values($media->variants['files'])];
        app(MediaService::class)->delete($media);
        foreach ($paths as $path) {
            Storage::disk($disk)->assertMissing($path);
        }
        $this->assertSame(0, Media::count());
    }

    public function test_cards_use_srcset_when_variants_exist(): void
    {
        $this->withoutVite();
        $disk = config('commerce.media_disk', 'public');
        Storage::fake($disk);
        $media = app(MediaService::class)->upload(UploadedFile::fake()->image('card.jpg', 1200, 1500), 'Card');
        $product = Product::factory()->create();
        Variant::factory()->create(['product_id' => $product->id]);
        $product->media()->attach($media->id, ['position' => 0]);
        $this->get('/shop')->assertOk()->assertSee('srcset="', false)->assertSee('480w', false);
    }
}
