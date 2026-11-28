# Ngói Photobooth

Hệ thống đặt lịch chụp ảnh tự phục vụ cho tiệm Ngói (2 chi nhánh, mỗi chi nhánh 3 phòng). Viết bằng **Laravel 12 + Livewire 4 + Tailwind CSS 4**, có REST API (Sanctum, Scramble), giám sát bằng Telescope và Pulse.

## Cấu trúc

```
.github/workflows   CI (lint, test SQLite + MySQL, tài liệu API, Docker), release, phân loại Issue
backend/            Ứng dụng Laravel: model, service, API, Livewire, test
frontend/           Giao diện: Blade view, Tailwind CSS, JS (Vite build từ backend)
data/catalog        Dữ liệu chi nhánh, phòng dùng cho seeder
deploy/             Dockerfile, docker-compose (app, queue, scheduler, pulse, mysql, redis)
design/             Prototype HTML, token màu và font
docs/               Đặc tả, kiến trúc, database, API, giám sát, hiệu năng, workflow
scripts/            Cài đặt, chạy dev, chạy test, tạo Issue GitHub
```

## Chạy thử

Cần PHP 8.3, Composer, Node 20, MySQL 8 (hoặc SQLite).

```bash
./scripts/setup.sh          # composer, npm, .env, key, migrate --seed, build
./scripts/dev.sh            # server, queue, scheduler, vite cùng lúc
```

Mở http://localhost:8000. Tài khoản mẫu, **chỉ được tạo khi `APP_ENV=local`** (mật khẩu `password`). Ở môi trường khác seeder bắt buộc đặt `SEED_ADMIN_PASSWORD` và chỉ tạo tài khoản chủ cửa hàng:

| Vai trò | Email |
|---|---|
| Chủ tiệm | admin@ngoi.vn |
| Nhân viên | nhanvien@ngoi.vn |
| Khách | khach@ngoi.vn |

Mã truy cập trong ngày hiện ở trang Quản lý > Cài đặt.

## Đường dẫn chính

- `/dat-lich`: đặt lịch (chọn chi nhánh, phòng, khung giờ, xác nhận, QR chuyển khoản)
- `/admin`: lịch phòng theo ngày, danh sách booking, báo cáo, phòng, lời nhắn
- `/docs/api`: tài liệu API (Scramble)
- `/telescope`, `/pulse`: giám sát (chỉ chủ tiệm)

## Kiểm thử

```bash
./scripts/test.sh           # Pint + Pest
cd backend && php artisan test --coverage
```

## Tài liệu

- [Đặc tả yêu cầu](docs/dac-ta-yeu-cau.md)
- [Kiến trúc](docs/kien-truc.md) · [Database](docs/database.md) · [API](docs/api.md)
- [Giám sát và log](docs/giam-sat.md) · [Hiệu năng](docs/hieu-nang.md)
- [Workflow dự án](docs/workflow-du-an.md) · [Hướng dẫn GitHub](docs/huong-dan-github.md)
- [Đối chiếu tiêu chí chấm](docs/doi-chieu-tieu-chi.md)
