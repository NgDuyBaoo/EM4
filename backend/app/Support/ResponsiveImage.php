<?php

namespace App\Support;

/**
 * Tạo srcset cho ảnh đã cắt sẵn nhiều cỡ: <tên>-480.webp, <tên>-800.webp, <tên>-1200.webp và <tên>-800.jpg (dự phòng).
 * Độ rộng phải khớp WIDTHS trong scripts/optimize-images.py.
 */
final class ResponsiveImage
{
    public const WIDTHS = [480, 800, 1200];

    public const FALLBACK_WIDTH = 800;

    public function __construct(public readonly string $basePath) {}

    /** "…-480.webp 480w, …-800.webp 800w, …-1200.webp 1200w" */
    public function srcset(): string
    {
        return collect(self::WIDTHS)
            ->map(fn (int $w) => asset("{$this->basePath}-{$w}.webp")." {$w}w")
            ->implode(', ');
    }

    public function fallback(): string
    {
        return asset("{$this->basePath}-".self::FALLBACK_WIDTH.'.jpg');
    }

    /** Đường dẫn tương đối (trong public/) của mọi file ảnh cần có. @return list<string> */
    public function files(): array
    {
        return [
            ...array_map(fn (int $w) => "{$this->basePath}-{$w}.webp", self::WIDTHS),
            "{$this->basePath}-".self::FALLBACK_WIDTH.'.jpg',
        ];
    }
}
