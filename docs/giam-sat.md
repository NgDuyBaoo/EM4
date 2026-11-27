# Ghi log & giám sát

| Công cụ | Ở đâu | Dùng để |
|---------|-------|---------|
| **Laravel Telescope** | `/telescope` (chỉ owner) · `app/Providers/TelescopeServiceProvider.php` | Xem từng request, query (đánh dấu query chậm), job, mail, notification, exception, lệnh lịch |
| **Laravel Pulse** | `/pulse` (chỉ owner) · `config/pulse.php` · container `pulse` chạy `pulse:check` | Giám sát **máy chủ** (CPU, RAM, ổ đĩa), request chậm, query chậm, job, hit rate cache, người dùng hoạt động |
| **Health check** | `GET /up` (Laravel) · `GET /api/health` | Kiểm tra DB, cache, độ dài hàng đợi; trả 503 khi có thành phần lỗi. Docker `HEALTHCHECK` và uptime monitor (UptimeRobot…) gọi endpoint này |
| **Log ứng dụng** | `storage/logs/laravel-YYYY-MM-DD.log` | Kênh `daily`, giữ 14 ngày; production ghi thêm `stderr` để Docker thu |
| **Log nghiệp vụ** | `storage/logs/booking-*.log` | Tạo booking, đổi trạng thái, từ chối đặt trùng (kèm mã lỗi) – giữ 30 ngày |
| **Log truy vấn chậm** | `storage/logs/slow-query-*.log` | Mọi câu SQL chậm hơn `NGOI_SLOW_QUERY_MS` (mặc định 500 ms), và request có tổng thời gian query > 2 giây |

## Xử lý lỗi (`backend/bootstrap/app.php`)
- API luôn trả JSON tiếng Việt, có `code` máy đọc được; không lộ stack trace khi `APP_DEBUG=false`.
- `BookingException` là lỗi nghiệp vụ: tự ghi vào log `booking` (`report()`), tự trả 409/422 (`render()`).
- Mọi log lỗi được gắn thêm `url`, `user_id` (`$exceptions->context`). `dontReportDuplicates()` tránh log lặp.
- Trang lỗi web tiếng Việt: `frontend/resources/views/errors/{403,404,419,429,500,503}.blade.php`.
- Gọi API ngoài (Open-Meteo) lỗi → ghi `warning`, trang vẫn hoạt động.

## Cảnh báo truy vấn chậm (`AppServiceProvider::configureSlowQueryAlerts`)
```php
DB::listen(fn (QueryExecuted $q) => $q->time >= config('ngoi.slow_query_ms') && Log::channel('slow-query')->warning(...));
DB::whenQueryingForLongerThan(2000, fn ($conn, $event) => Log::channel('slow-query')->error(...));
```
Có thể nối kênh `slow-query` sang Slack bằng driver `slack` của Laravel khi triển khai thật.
