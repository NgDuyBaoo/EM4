# Backend (Laravel 12)

- `app/Services`: nghiệp vụ đặt lịch, lịch trống, mã truy cập, báo cáo, thời tiết, VietQR
- `app/Http/Controllers/Api`: REST API (resource controller), `Web`: trang tĩnh và đăng nhập
- `app/Livewire`: màn hình khách (`Customer`) và quản lý (`Admin`)
- `routes/api.php`, `routes/web.php`, `routes/console.php` (lịch chạy job)
- `tests/`: Pest, `Unit` và `Feature` (Api, Web)

View và CSS nằm ở `../frontend/resources`, được nạp qua `config/view.php` và `vite.config.js`.

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
composer run dev
```
