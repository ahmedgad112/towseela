<?php

namespace Tests\Unit;

use App\Support\CatalogImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogImageTest extends TestCase
{
    public function test_store_optimizes_uploads_to_webp_and_reduces_dimensions(): void
    {
        Storage::fake('public');

        $upload = UploadedFile::fake()->image('store.png', 2000, 1200);
        $path = CatalogImage::store($upload, 'businesses', null, 800);

        $this->assertNotNull($path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $binary = Storage::disk('public')->get($path);
        $image = imagecreatefromstring($binary);

        $this->assertNotFalse($image);
        $this->assertSame(800, imagesx($image));
        $this->assertSame(480, imagesy($image));
        $this->assertLessThan($upload->getSize(), strlen($binary));

        imagedestroy($image);
    }

    public function test_store_keeps_existing_path_when_no_file_is_provided(): void
    {
        $this->assertSame('businesses/old.webp', CatalogImage::store(null, 'businesses', 'businesses/old.webp'));
    }
}
