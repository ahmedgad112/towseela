<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CatalogImage
{
    /**
     * @return array<int, string>
     */
    public static function rules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];
    }

    public static function store(?UploadedFile $file, string $directory, ?string $existing = null): ?string
    {
        if ($file === null) {
            return $existing;
        }

        self::delete($existing);

        return $file->store($directory, 'public');
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
}
