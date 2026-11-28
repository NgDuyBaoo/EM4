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
Ngoài ghi log, mỗi lần vượt ngưỡng hệ thống còn **gửi cảnh báo** (`app/Monitoring/SlowQueryAlert.php`, job `SendSlowQueryAlert` chạy qua queue):

- Email tới `NGOI_ALERT_MAIL`, để trống thì gửi cho mọi tài khoản chủ cửa hàng.
- Slack nếu đặt `NGOI_ALERT_SLACK_WEBHOOK` (Incoming Webhook).
- Cùng một câu SQL chỉ báo 1 lần trong `NGOI_SLOW_QUERY_ALERT_THROTTLE` phút (mặc định 10) để không gửi dồn dập. Tắt bằng `NGOI_SLOW_QUERY_ALERT=false`.

## Cảnh báo lỗi nghiêm trọng

Mọi exception không được xử lý (khách nhận trang lỗi 500) được ghi log như mặc định và **báo ngay** cho chủ tiệm (`app/Monitoring/ErrorAlert.php`, job `SendErrorAlert` chạy qua queue):

- Gửi cùng nơi nhận với cảnh báo truy vấn chậm: email `NGOI_ALERT_MAIL` (để trống thì gửi mọi tài khoản chủ cửa hàng) và Slack `NGOI_ALERT_SLACK_WEBHOOK`.
- Không báo lỗi nghiệp vụ (`BookingException`), lỗi 4xx, lỗi validate.
- Cùng một lỗi (class + file + dòng) chỉ báo 1 lần / `NGOI_ERROR_ALERT_THROTTLE` phút (mặc định 10). Tắt bằng `NGOI_ERROR_ALERT=false`.
- Lỗi SQL chỉ gửi câu lệnh có dấu `?`, không gửi giá trị (tránh lộ tên, số điện thoại khách qua email/Slack).

## Giám sát máy chủ: mạng và cảnh báo theo ngưỡng

Container `pulse` chạy `php artisan pulse:check`, ngoài recorder mặc định (CPU, RAM, đĩa) còn có 2 recorder tự viết:

| Recorder | Việc làm | Xem ở đâu |
|---|---|---|
| `App\Pulse\Recorders\Network` | Đọc `/proc/net/dev` mỗi 15 giây, tính tốc độ nhận/gửi (byte/giây), lưu trung bình và cao nhất | Thẻ **Mạng** trên `/pulse` (`frontend/resources/views/vendor/pulse/dashboard.blade.php`) |
| `App\Pulse\Recorders\ServerThresholds` | Mỗi phút kiểm CPU, RAM, đĩa; vượt ngưỡng thì gửi email/Slack (job `SendServerAlert`), mỗi chỉ số báo tối đa 1 lần / 30 phút | Email, Slack |

Ngưỡng mặc định: CPU 85%, RAM 90%, đĩa 90%. Đổi bằng `NGOI_ALERT_CPU_PERCENT`, `NGOI_ALERT_MEMORY_PERCENT`, `NGOI_ALERT_DISK_PERCENT`; tắt bằng `NGOI_SERVER_ALERT=false`.

Khi tải cao nên để Pulse ghi qua Redis (`PULSE_INGEST_DRIVER=redis`, Redis đã có trong `deploy/docker-compose.yml`) để không ghi trực tiếp vào MySQL chính.
