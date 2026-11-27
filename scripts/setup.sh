#!/usr/bin/env bash
# Cài đặt lần đầu trên máy dev (cần PHP 8.3, Composer, Node 20+, MySQL 8).
set -euo pipefail
cd "$(dirname "$0")/../backend"

composer install
npm install
[ -f .env ] || { cp .env.example .env; php artisan key:generate; echo "==> Đã tạo backend/.env – kiểm tra DB_* cho đúng MySQL của bạn"; }
php artisan migrate --seed
npm run build
echo
echo "Xong. Chạy scripts/dev.sh rồi mở http://localhost:8000"
echo "Tài khoản: admin@ngoi.vn / password (chủ cửa hàng), nhanvien@ngoi.vn / password, khach@ngoi.vn / password"
