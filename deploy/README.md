# deploy

| File | Vai trò |
|------|---------|
| `docker/app.Dockerfile` | Image Laravel: cài vendor, build Tailwind/Vite, chạy PHP-FPM + Nginx (serversideup/php) |
| `docker-compose.yml` | `app` (web), `queue` (gửi tin Zalo cho khách, cảnh báo cho chủ tiệm), `scheduler` (tự hủy booking trễ, nhắc giờ chụp), `pulse` (giám sát máy chủ), `mysql`, `redis` |
| `.env.example` | Biến môi trường cần điền |

## Chạy

```bash
cp deploy/.env.example deploy/.env       # điền mật khẩu, APP_KEY
docker compose -f deploy/docker-compose.yml --env-file deploy/.env up -d --build
docker compose -f deploy/docker-compose.yml exec -e SEED_ADMIN_PASSWORD="mật-khẩu-mạnh" app php artisan db:seed --force   # lần đầu: danh mục + tài khoản chủ cửa hàng (bắt buộc đặt mật khẩu)
```

Muốn có booking và lời nhắn mẫu để chạy thử, chụp ảnh demo (Pulse, báo cáo): thêm `-e SEED_DEMO_DATA=true` vào lệnh seed trên. Seeder không dùng Faker nên chạy được trong image production.

Container `app` tự chạy migration khi khởi động (`AUTORUN_LARAVEL_MIGRATION`). Đặt sau reverse proxy có HTTPS.

## Giám sát
- `GET /api/health`: kiểm tra DB, cache, queue (Docker healthcheck dùng endpoint này).
- `/pulse`: CPU, RAM, ổ đĩa, request chậm, query chậm, job, cache hit rate.
- `/telescope`: chi tiết từng request, query, job, mail, exception (chỉ chủ cửa hàng).
- `backend/storage/logs/`: `laravel-*.log`, `booking-*.log`, `slow-query-*.log`.
