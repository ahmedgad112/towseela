<?php

use App\Support\CatalogImage;

require __DIR__.'/../vendor/autoload.php';

$directory = __DIR__.'/../public/image';

$results = [
    'cover.webp' => CatalogImage::optimizeFile($directory.'/cover.png', $directory.'/cover.webp', 1600, 72),
    'logo.webp' => CatalogImage::optimizeFile($directory.'/logo.png', $directory.'/logo.webp', 512, 80),
];

foreach ($results as $name => $bytes) {
    echo $name.' => '.round($bytes / 1024, 1)." KB\n";
}
