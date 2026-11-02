<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CatalogImage
{
    public const COVER_MAX_WIDTH = 1280;

    public const PRODUCT_MAX_WIDTH = 800;

    public const WEBP_QUALITY = 74;

    /**
     * @return array<int, string>
     */
    public static function rules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }

    public static function store(?UploadedFile $file, string $directory, ?string $existing = null, int $maxWidth = self::COVER_MAX_WIDTH): ?string
    {
        if ($file === null) {
            return $existing;
        }

        self::delete($existing);

        $path = trim($directory, '/').'/'.Str::random(40).'.webp';
        $binary = self::optimizeToWebp($file->getRealPath(), $maxWidth);

        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    public static function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    public static function delete(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function optimizeFile(string $sourcePath, string $destinationPath, int $maxWidth = self::COVER_MAX_WIDTH, int $quality = self::WEBP_QUALITY): int
    {
        $binary = self::optimizeToWebp($sourcePath, $maxWidth, $quality);
        $directory = dirname($destinationPath);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create directory [{$directory}].");
        }

        if (file_put_contents($destinationPath, $binary) === false) {
            throw new RuntimeException("Unable to write optimized image [{$destinationPath}].");
        }

        return strlen($binary);
    }

    private static function optimizeToWebp(string $sourcePath, int $maxWidth, int $quality = self::WEBP_QUALITY): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw new RuntimeException('PHP GD with WebP support is required to optimize images.');
        }

        $contents = file_get_contents($sourcePath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read image [{$sourcePath}].");
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException("Unable to decode image [{$sourcePath}].");
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth) {
            $targetHeight = (int) max(1, round($height * ($maxWidth / $width)));
            $resized = imagecreatetruecolor($maxWidth, $targetHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $maxWidth, $targetHeight, $transparent);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $targetHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        } else {
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        ob_start();
        $ok = imagewebp($image, null, $quality);
        $binary = ob_get_clean();
        imagedestroy($image);

        if (! $ok || $binary === false || $binary === '') {
            throw new RuntimeException('Unable to encode WebP image.');
        }

        return $binary;
    }
}
