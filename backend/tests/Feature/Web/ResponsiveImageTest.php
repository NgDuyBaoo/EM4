<?php

use App\Support\ResponsiveImage;

it('trang chủ hiện phông nền bằng WebP nhiều cỡ, srcset, sizes và lazy-load', function () {
    seedBranches();

    $html = $this->get('/')->assertOk()->getContent();

    foreach (config('ngoi.backdrops') as $bd) {
        expect($html)->toContain("images/backdrops/{$bd['slug']}-480.webp 480w")
            ->toContain("images/backdrops/{$bd['slug']}-1200.webp 1200w")
            ->toContain("images/backdrops/{$bd['slug']}-800.jpg")
            ->toContain('Phông nền '.$bd['name']);
    }
    expect($html)->toContain('type="image/webp"')
        ->toContain('sizes="(min-width: 1024px) 240px, 50vw"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"');
});

it('đủ file ảnh cho mọi phông nền, mỗi file dưới 300 KB và không còn EXIF', function () {
    foreach (config('ngoi.backdrops') as $bd) {
        foreach ((new ResponsiveImage('images/backdrops/'.$bd['slug']))->files() as $file) {
            $path = public_path($file);
            expect(is_file($path))->toBeTrue("thiếu $file");
            $bytes = file_get_contents($path);
            expect(strlen($bytes))->toBeLessThan(300 * 1024, "$file quá nặng")
                // EXIF nằm trong khối APP1 "Exif\0\0" (JPEG) hoặc chunk "EXIF" (WebP)
                ->and(str_contains($bytes, "Exif\0\0") || str_contains(substr($bytes, 0, 64), 'EXIF'))->toBeFalse("$file còn EXIF");
        }
    }
});

it('width/height trong config khớp tỉ lệ ảnh thật để không nhảy layout', function () {
    foreach (config('ngoi.backdrops') as $bd) {
        [$w, $h] = getimagesize(public_path("images/backdrops/{$bd['slug']}-800.jpg"));
        expect(round($w / $h, 2))->toBe(round($bd['width'] / $bd['height'], 2));
    }
});
