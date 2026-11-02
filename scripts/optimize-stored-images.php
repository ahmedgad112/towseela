<?php

use App\Models\Business;
use App\Models\Product;
use App\Support\CatalogImage;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$disk = Storage::disk('public');

$optimizeModelPath = function (?string $path, int $maxWidth) use ($disk): ?string {
    if (! filled($path) || ! $disk->exists($path)) {
        return $path;
    }

    $absolute = $disk->path($path);
    $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

    if ($extension === 'webp') {
        $before = filesize($absolute) ?: 0;
        CatalogImage::optimizeFile($absolute, $absolute, $maxWidth);
        $after = filesize($absolute) ?: 0;
        echo "recompressed {$path}: ".round($before / 1024, 1).'KB -> '.round($after / 1024, 1)."KB\n";

        return $path;
    }

    $newPath = preg_replace('/\.[^.]+$/', '.webp', $path) ?: ($path.'.webp');
    CatalogImage::optimizeFile($absolute, $disk->path($newPath), $maxWidth);
    $disk->delete($path);
    echo "converted {$path} -> {$newPath} (".round(($disk->size($newPath) ?: 0) / 1024, 1)."KB)\n";

    return $newPath;
};

Business::query()->whereNotNull('cover_path')->each(function (Business $business) use ($optimizeModelPath): void {
    $business->forceFill([
        'cover_path' => $optimizeModelPath($business->cover_path, CatalogImage::COVER_MAX_WIDTH),
    ])->save();
});

Product::query()->whereNotNull('image_path')->each(function (Product $product) use ($optimizeModelPath): void {
    $product->forceFill([
        'image_path' => $optimizeModelPath($product->image_path, CatalogImage::PRODUCT_MAX_WIDTH),
    ])->save();
});

echo "done\n";
