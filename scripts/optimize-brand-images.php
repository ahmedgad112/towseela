<?php

use App\Support\CatalogImage;

require __DIR__.'/../vendor/autoload.php';

$directory = __DIR__.'/../public/image';

$results = [
    'cover.webp' => CatalogImage::optimizeFile($directory.'/cover.png', $directory.'/cover.webp', 1600, 72),
    'logo.webp' => CatalogImage::optimizeFile($directory.'/logo.png', $directory.'/logo.webp', 512, 80),
];

$coverPath = $directory.'/cover.webp';
if (is_file($coverPath) && function_exists('imagecreatefromwebp')) {
    $src = imagecreatefromwebp($coverPath);
    $sw = imagesx($src);
    $sh = imagesy($src);
    $ch = 500;
    $cw = (int) round($ch * 0.8);
    $cx = max(0, $sw - $cw - 70);
    $cy = 20;
    $dst = imagecreatetruecolor($cw, $ch);
    imagecopy($dst, $src, 0, 0, $cx, $cy, $cw, $ch);
    imagewebp($dst, $directory.'/cover-mobile.webp', 80);
    imagedestroy($dst);
    imagedestroy($src);
    $results['cover-mobile.webp'] = filesize($directory.'/cover-mobile.webp');
}

foreach ($results as $name => $bytes) {
    echo $name.' => '.round($bytes / 1024, 1)." KB\n";
}
