{{--
    Ảnh nhiều cỡ: trình duyệt tự chọn WebP vừa với độ rộng hiển thị (srcset + sizes), JPEG cho trình duyệt cũ.
    loading="lazy" + decoding="async": chỉ tải khi gần cuộn tới, không chặn hiển thị trang.
    width/height giữ đúng tỉ lệ để trình duyệt chừa chỗ trước khi ảnh tải xong (tránh nhảy layout, CLS).
--}}
@props(['src', 'alt', 'width', 'height', 'sizes' => '100vw', 'lazy' => true])
@php($image = new \App\Support\ResponsiveImage($src))
<picture>
    <source type="image/webp" srcset="{{ $image->srcset() }}" sizes="{{ $sizes }}">
    <img src="{{ $image->fallback() }}" alt="{{ $alt }}" width="{{ $width }}" height="{{ $height }}"
         @if ($lazy) loading="lazy" @endif decoding="async" {{ $attributes }}>
</picture>
