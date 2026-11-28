# Đối chiếu tiêu chí chấm điểm

Bảng dưới chỉ ra từng tiêu chí và nơi chứng minh trong repo (đường dẫn tính từ thư mục gốc). Cập nhật ngày 28/11/2026, sau khi merge PR #31–#45.

| Mục | Tiêu chí | Bằng chứng |
|---|---|---|
| 1a | Source code trên GitHub | https://github.com/NgDuyBaoo/NgoiBooking |
| 1b | Commit đúng chuẩn | Conventional Commits; từ PR #39 ghi số issue ở tiêu đề `type(scope): mô tả (#n)` (xem `CONTRIBUTING.md`) |
| 1c | GitHub Issues | Issue có mô tả, **Tiêu chí hoàn thành**, nhãn và người nhận; mẫu ở `.github/ISSUE_TEMPLATE/` |
| 1d | Workflow (nhánh, PR, nối issue) | PR #21, #24, #25, #31–#37 từ nhánh chủ đề, có `Closes #n`, CI chạy trên PR; quy trình review ở `CONTRIBUTING.md`; PR #39, #41, #43, #45 có Approve của thành viên trước khi merge ([ảnh](assets/github-pr-review.png)) |
| 2a | Thiết kế CSDL, migration | `backend/database/migrations/`, ERD trong `docs/database.md` |
| 2b | Seed | `backend/database/seeders/`, `backend/database/factories/`; CI chạy `migrate:fresh --seed` trên MySQL |
| 3a | Blade/Livewire + Tailwind | `frontend/resources/views/`, `backend/app/Livewire/` |
| 3b | Đăng ký, đăng nhập | `backend/app/Http/Controllers/Web/AuthController.php`, `backend/app/Http/Requests/Web/` |
| 3c | CSRF + validate server | `@csrf`, FormRequest, test 419 `backend/tests/Feature/Web/AuthWebTest.php` |
| 3e | Truy vấn, lọc | `backend/app/Livewire/Admin/Bookings.php` |
| 3f | API bên ngoài | Open-Meteo `WeatherService`, VietQR `BankDirectory` + `VietQr` (có hỗ trợ khóa `x-client-id`/`x-api-key`); lệnh `ngoi:check-integrations` chạy trên CI; kết quả thật ở `docs/integrations/README.md` |
| 3g | Unit + integration test (web) | `backend/tests/Unit/`, `backend/tests/Feature/Web/`, `backend/tests/Feature/Services/` |
| 4a | Resource controller CRUD | `backend/routes/api.php`, `backend/app/Http/Controllers/Api/`, `backend/app/Http/Resources/` |
| 4b | Sanctum | Token có ability `customer`/`staff`; đổi trạng thái booking cần `tokenCan('staff')` |
| 4c | Scramble | `/docs/api`, `docs/api/openapi.json`, CI chạy `scramble:analyze`; ảnh: [tổng quan](assets/scramble-docs.png), [một endpoint](assets/scramble-endpoint.png) |
| 4d | Test API | `backend/tests/Feature/Api/`, `backend/tests/Unit/PolicyTest.php`, `BookingResourceTest.php` |
| 5a | Telescope | `backend/app/Providers/TelescopeServiceProvider.php` |
| 5b | Xử lý lỗi, logging | `backend/bootstrap/app.php`; lỗi 500 báo email/Slack `backend/app/Monitoring/ErrorAlert.php`; `docs/giam-sat.md` |
| 5c | Cảnh báo truy vấn chậm | `backend/app/Monitoring/SlowQueryAlert.php`, job `SendSlowQueryAlert` |
| 5d | Giám sát server | Pulse + recorder `Network`, `ServerThresholds` (`backend/app/Pulse/Recorders/`), thẻ Mạng trên `/pulse`; [ảnh dashboard chạy Docker](assets/pulse-dashboard.png) |
| 6a | Bất đồng bộ | Job `SendBookingConfirmation`, `SendSlowQueryAlert`, `SendErrorAlert`, `SendServerAlert`; scheduler `backend/routes/console.php` |
| 6b | Tối ưu tải tài nguyên | Vite build + prefetch, `wire:navigate`, `#[Lazy]`, ảnh `loading="lazy"`, cache toàn trang `CachePublicPage`, asset `immutable` |
| 6c | Cache | Cache dữ liệu phía server + header `Cache-Control`/`ETag` (304), `no-store` cho API cần token; `docs/hieu-nang.md` |
| 6d | Antipattern | Eager loading, `Model::shouldBeStrict()`, test đếm truy vấn |

## Kết quả kiểm tra (commit `4bfeb07`)

- `php artisan test`: 180 test, 562 assertion, đều đạt (SQLite); CI chạy thêm MySQL 8.4.
- `vendor/bin/pint --test`: sạch.
- GitHub Actions: Pint, test SQLite, test MySQL, Scramble, gọi thật API ngoài, build Docker đều xanh.

## Còn thiếu

- Ảnh email cảnh báo thật (máy chủ, lỗi 500, truy vấn chậm), xem `docs/assets/README.md`.
- Ảnh phòng thật để dùng WebP + `srcset` (6b).
- Các PR từ #21 đến #37 chưa có review (đã merge trước khi áp dụng quy trình); từ PR #39 trở đi đều có Approve.
