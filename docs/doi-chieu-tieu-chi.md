# Đối chiếu tiêu chí chấm điểm

Bảng dưới chỉ ra từng tiêu chí trong đề và file chứng minh trong repo. Đường dẫn tính từ thư mục gốc.

| Task | Tiêu chí | Đã làm | Bằng chứng |
|---|---|---|---|
| 1. Git (1đ) | Repo có cấu trúc, commit rõ ràng, Issue, workflow | Commit theo Conventional Commits, mẫu Issue/PR, nhãn, script tạo Issue, CI | `git log`, `.github/ISSUE_TEMPLATE/`, `.github/pull_request_template.md`, `.github/labels.yml`, `scripts/create-github-issues.sh`, `docs/workflow-du-an.md`, `docs/huong-dan-github.md` |
| 2. Database (1đ) | Migration, seeder, quan hệ, ràng buộc | 5 bảng nghiệp vụ, khóa ngoại, chỉ mục, cột sinh `active_slot` + UNIQUE chống đặt trùng, seeder có booking mẫu | `backend/database/migrations/`, `backend/database/seeders/`, `backend/database/factories/`, `docs/database.md` |
| 3. UI (3đ) | Laravel Blade/Livewire + Tailwind | Blade layout + 8 component Livewire, Tailwind 4 | `frontend/resources/views/`, `frontend/resources/css/app.css`, `backend/app/Livewire/` |
| | Đăng ký, đăng nhập | Form đăng ký/đăng nhập, giới hạn 5 lần thử | `backend/app/Http/Controllers/Web/AuthController.php`, `backend/app/Http/Requests/Web/` |
| | CSRF + validate phía server | `@csrf` trên mọi form, FormRequest, Livewire `rules()` | `frontend/resources/views/auth/`, `backend/app/Http/Requests/` |
| | Truy vấn, lọc | Lọc booking theo ngày/chi nhánh/trạng thái/từ khóa, phân trang, `#[Url]` | `backend/app/Livewire/Admin/Bookings.php` |
| | API bên ngoài (1đ) | Thời tiết Open-Meteo (timeout, retry, cache), ảnh QR chuyển khoản VietQR | `backend/app/Services/WeatherService.php`, `backend/app/Services/VietQr.php` |
| | Test (1đ) | Test Feature cho web và Livewire | `backend/tests/Feature/Web/` |
| 4. REST API (2đ) | Resource controller, CRUD đầy đủ | `apiResource` cho branches, rooms, bookings, messages | `backend/routes/api.php`, `backend/app/Http/Controllers/Api/` |
| | Sanctum | Token có ability `customer`/`staff`, middleware `abilities` | `backend/app/Http/Controllers/Api/AuthController.php`, `backend/bootstrap/app.php` |
| | Scramble | Tài liệu tại `/docs/api`, xuất `docs/api/openapi.json` (19 path) | `backend/config/scramble.php`, `docs/api/openapi.json`, `docs/api.md` |
| | Test | Test API cho auth, danh mục, booking, admin | `backend/tests/Feature/Api/` |
| 5. Logging/Monitoring (1đ) | Telescope | Bật, chỉ chủ tiệm xem được | `backend/app/Providers/TelescopeServiceProvider.php` |
| | Xử lý lỗi, log | `BookingException` có mã lỗi, JSON lỗi thống nhất cho API, trang lỗi riêng, kênh log `booking` | `backend/app/Exceptions/BookingException.php`, `backend/bootstrap/app.php`, `frontend/resources/views/errors/` |
| | Cảnh báo truy vấn chậm | `DB::listen` theo ngưỡng `NGOI_SLOW_QUERY_MS`, `whenQueryingForLongerThan`, kênh log `slow-query` | `backend/app/Providers/AppServiceProvider.php`, `docs/giam-sat.md` |
| | Giám sát máy chủ | Laravel Pulse (`/pulse`), health check `/api/health`, healthcheck Docker | `backend/config/pulse.php`, `backend/app/Http/Controllers/Api/SystemController.php`, `deploy/` |
| 6. Hiệu năng (2đ) | Xử lý bất đồng bộ | Job gửi xác nhận qua queue, scheduler hủy khách không đến | `backend/app/Jobs/`, `backend/app/Console/Commands/CancelNoShows.php`, `backend/routes/console.php` |
| | Tải tài nguyên | Vite build + prefetch, `wire:navigate`, `#[Lazy]` cho báo cáo | `backend/vite.config.js`, `backend/app/Livewire/Admin/Reports.php` |
| | Cache | Cache danh mục, lịch trống, báo cáo, thời tiết; xóa cache bằng observer | `backend/app/Services/`, `backend/app/Observers/` |
| | Chống antipattern | Eager loading, `Model::shouldBeStrict()` chặn N+1, `withCount/withSum`, insert theo lô | `docs/hieu-nang.md` |

## Kết quả kiểm tra

- `php artisan test`: 75 test, 299 assertion, đều đạt trên SQLite và MySQL.
- `vendor/bin/pint --test`: sạch.
- `php artisan scramble:analyze`: không cảnh báo.
- Docker build và GitHub Actions chưa chạy thử được trong môi trường làm bài, cần chạy khi đẩy lên GitHub.
