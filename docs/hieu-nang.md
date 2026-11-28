# Mở rộng & tối ưu hiệu năng

## Xử lý bất đồng bộ
| Việc | Cách làm |
|------|---------|
| Gửi tin Zalo ZNS cho khách (xác nhận, nhắc lịch, báo hủy) | `App\Jobs\SendBookingZalo` (ShouldQueue, thử lại 3 lần, backoff 10s/60s). Khách nhận kết quả ngay, không chờ gọi API Zalo |
| Tự hủy booking quá giờ | Lệnh `bookings:cancel-no-shows`, scheduler chạy mỗi 5 phút (`routes/console.php`) |
| Dọn dữ liệu giám sát | `telescope:prune`, `sanctum:prune-expired` hằng ngày |
| Thu thập số liệu máy chủ | `pulse:check` chạy nền |
Production dùng Redis cho queue; container `queue` và `scheduler` tách khỏi web để mở rộng độc lập.

## Cache
| Dữ liệu | Key | Thời gian | Xóa khi |
|---------|-----|-----------|---------|
| Danh mục chi nhánh + phòng | `catalog:branches` | 10 phút | Sửa chi nhánh/phòng (`CatalogObserver`) |
| Lịch trống chi nhánh/ngày | `availability:{branch}:{date}` | 60 giây | Có booking thay đổi (`BookingObserver`) |
| Mã truy cập hôm nay | `access-code:{date}` | đến 24:00 | Admin tạo mã mới |
| Báo cáo thống kê | `report:{from}:{to}:{branch}` | 5 phút | Hết hạn |
| Thời tiết Huế | `weather:hue:{giờ}` | 30 phút (lỗi: 5 phút) | Hết hạn |
| Computed Livewire | `#[Computed(persist: true)]` | 10 phút | – |
Trạng thái "đã qua giờ" luôn tính lại theo giờ hiện tại nên cache không làm sai lịch. Production: `CACHE_STORE=redis`;
khởi động container chạy `config:cache`, `route:cache`, `view:cache`, `event:cache` (AUTORUN của image) và bật OPcache.

## Tối ưu tải tài nguyên
- Vite build, minify, đặt tên file theo hash (cache trình duyệt lâu dài); Tailwind 4 chỉ sinh class được dùng (CSS ~35 KB, gzip ~7 KB).
- `Vite::prefetch()` tải trước asset; `wire:navigate` chuyển trang kiểu SPA (không tải lại CSS/JS).
- Trang Thống kê dùng `#[Lazy]`: hiện khung chờ ngay, số liệu tải sau.
- Ảnh VietQR `loading="lazy"` có `width/height` cố định (không giật layout); font Google có `preconnect` + `display=swap`.
- Ảnh phông nền trên trang chủ (`<x-responsive-image>`, `App\Support\ResponsiveImage`):
  - Mỗi ảnh có 3 bản **WebP** (480, 800, 1200px) và 1 bản JPEG 800px dự phòng. Thẻ `<picture>` + `srcset`/`sizes` để trình duyệt tải đúng cỡ: màn hình máy tính (thẻ rộng 240px) và điện thoại đều chỉ tải bản 480px, khoảng 9–140 KB mỗi ảnh, thay vì ảnh gốc 0,6–1 MB.
  - `loading="lazy"`, `decoding="async"`, có `width`/`height` để không nhảy layout.
  - Ảnh được tạo bằng `scripts/optimize-images.py` (Pillow): thu nhỏ ảnh nguồn còn tối đa 1600px, lưu ở `design/photos/backdrops/`, bỏ EXIF, ảnh hoa văn dày tự giảm chất lượng cho tới khi dưới ~200 KB.
  - Test `tests/Feature/Web/ResponsiveImageTest.php` kiểm tra HTML, đủ file, mỗi file dưới 300 KB, không còn EXIF và tỉ lệ khớp `width`/`height`.
- Danh sách dùng phân trang (`paginate`), API giới hạn `per_page ≤ 100`; lịch tự làm mới bằng `wire:poll.30s` thay vì tải lại trang.

## Tránh antipattern
| Antipattern | Cách tránh | Kiểm chứng |
|-------------|-----------|-----------|
| N+1 query | Eager loading `with(['room','branch'])`, `withCount`, `withSum`; `Model::shouldBeStrict()` báo lỗi lazy-load khi dev/test | Test đếm số query trong `CatalogApiTest`, `BookingApiTest` |
| Đếm/tổng trong vòng lặp PHP | `GROUP BY` + `SUM(CASE…)` 1 truy vấn trong `ReportService` | |
| Chèn từng dòng | `Booking::insert()` theo lô 500 dòng trong seeder | |
| Race condition đặt trùng | Chỉ số UNIQUE + transaction, `lockForUpdate()` khi đổi trạng thái | Test `database chặn trùng…` |
| Mass assignment | `$fillable` rõ ràng; `role` không nằm trong fillable | |
| Logic lặp ở nhiều nơi | Service dùng chung cho API và Livewire | |
| Gọi API ngoài không giới hạn | `timeout(4)`, `retry(2)`, cache, có fallback | Test `Http::fake` |
| Dò mã / mật khẩu | Rate limit `access-code`, `login`, `api` | Test khóa đăng nhập |
| Truy vấn không index | Chỉ số theo đúng điều kiện lọc (xem `docs/database.md`) | Log truy vấn chậm |

## Cache HTTP và cache toàn trang

| Lớp | Cách làm | File |
|---|---|---|
| API danh mục công khai | `Cache-Control: public, max-age=300` + `ETag`; client gửi `If-None-Match` đúng thì nhận **304** rỗng | `routes/api.php` (middleware `cache.headers`) |
| API thời tiết | `public, max-age=600` + `ETag` | `routes/api.php` |
| API cần token | `Cache-Control: no-store, private` để trình duyệt/proxy không lưu dữ liệu cá nhân | `app/Http/Middleware/NoStoreResponse.php` |
| Trang chủ, liên hệ (khách chưa đăng nhập) | Cache toàn trang HTML 10 phút (prerender lần đầu, các lần sau trả thẳng, header `X-Page-Cache: HIT`); token CSRF được điền lại theo phiên; đổi chi nhánh/phòng thì dựng lại | `app/Http/Middleware/CachePublicPage.php`, `app/Observers/CatalogObserver.php` |
| Asset Vite (`/build/*`) | Tên file có hash nên cache 1 năm `immutable` | `deploy/docker/nginx-cache.conf`, `backend/public/.htaccess` |
