#!/usr/bin/env python3
"""Tạo ảnh web (WebP nhiều kích thước + JPEG dự phòng) cho phông nền phòng chụp.

    pip install Pillow
    python3 scripts/optimize-images.py              # đọc design/photos/backdrops/*.jpg
    python3 scripts/optimize-images.py anh.jpg ten  # thêm ảnh mới: thu nhỏ về 1600px rồi lưu làm nguồn

Kết quả ở backend/public/images/backdrops/<ten>-<rộng>.webp (480/800/1200) và <ten>-800.jpg.
Ảnh đầu ra không giữ EXIF (Pillow chỉ ghi EXIF khi được truyền vào). Độ rộng phải khớp
App\\Support\\ResponsiveImage::WIDTHS.
"""
import sys
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "design/photos/backdrops"
OUT = ROOT / "backend/public/images/backdrops"
WIDTHS = (480, 800, 1200)
FALLBACK_WIDTH = 800          # JPEG cho trình duyệt không hỗ trợ WebP (rất ít), chỉ 1 cỡ
SOURCE_MAX = 1600
MAX_BYTES = 200 * 1024        # ảnh hoa văn dày (da báo) giảm chất lượng tới khi dưới ngưỡng


def load(path: Path) -> Image.Image:
    # Xoay theo EXIF Orientation trước khi bỏ EXIF, rồi chuyển sang RGB.
    return ImageOps.exif_transpose(Image.open(path)).convert("RGB")


def add_source(path: Path, name: str) -> Path:
    im = load(path)
    im.thumbnail((SOURCE_MAX, SOURCE_MAX), Image.LANCZOS)
    dest = SOURCE / f"{name}.jpg"
    im.save(dest, "JPEG", quality=80, optimize=True, progressive=True)
    return dest


def save_within_budget(im: Image.Image, dest: Path, fmt: str) -> int:
    for quality in range(80, 40, -5):
        opts = {"quality": quality, "method": 6} if fmt == "WEBP" else {"quality": quality, "optimize": True, "progressive": True}
        im.save(dest, fmt, **opts)
        if dest.stat().st_size <= MAX_BYTES:
            break
    return quality


def build(path: Path) -> None:
    im = load(path)
    for w in WIDTHS:
        h = round(im.height * w / im.width)
        resized = im.resize((w, h), Image.LANCZOS)
        q = save_within_budget(resized, OUT / f"{path.stem}-{w}.webp", "WEBP")
        if w == FALLBACK_WIDTH:
            save_within_budget(resized, OUT / f"{path.stem}-{w}.jpg", "JPEG")
        print(f"  {path.stem}-{w}.webp q={q}")


if __name__ == "__main__":
    SOURCE.mkdir(parents=True, exist_ok=True)
    OUT.mkdir(parents=True, exist_ok=True)
    if len(sys.argv) == 3:
        build(add_source(Path(sys.argv[1]), sys.argv[2]))
    else:
        for src in sorted(SOURCE.glob("*.jpg")):
            build(src)
