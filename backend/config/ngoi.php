<?php

// Quy tắc nghiệp vụ Ngói Photobooth (docs/dac-ta-yeu-cau.md, mục 3).
return [
    'slot_minutes' => 10,
    'default_open' => '08:30',
    'default_close' => '22:30',
    'max_slots_per_booking' => 12,
    // Quá giờ hẹn bao nhiêu phút mà chưa check-in thì hệ thống tự hủy (BR-07).
    'no_show_grace_minutes' => (int) env('NGOI_NO_SHOW_GRACE_MINUTES', 15),
    // Truy vấn chậm hơn ngưỡng này (ms) sẽ được ghi vào log "slow-query".
    'slow_query_ms' => (int) env('NGOI_SLOW_QUERY_MS', 500),

    // Mã QR chuyển khoản qua VietQR (https://www.vietqr.io).
    'vietqr' => [
        'bank_bin' => env('NGOI_BANK_BIN', '970436'),
        'account' => env('NGOI_BANK_ACCOUNT', '0000000000'),
        'account_name' => env('NGOI_BANK_ACCOUNT_NAME', 'NGOI PHOTOBOOTH'),
    ],

    // Dự báo thời tiết Huế từ Open-Meteo (miễn phí, không cần API key).
    'weather' => [
        'url' => 'https://api.open-meteo.com/v1/forecast',
        'latitude' => 16.4637,
        'longitude' => 107.5909,
        'cache_minutes' => 30,
    ],
];
