<?php

use Illuminate\Support\Facades\Schedule;

// BR-07: mỗi 5 phút hủy booking quá giờ hẹn mà khách chưa đến
Schedule::command('bookings:cancel-no-shows')->everyFiveMinutes()->withoutOverlapping();

// Dọn dữ liệu giám sát cũ
Schedule::command('telescope:prune --hours=72')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Pulse: ghi thông số CPU / RAM / ổ đĩa máy chủ (chạy nền bằng `php artisan pulse:check`)
