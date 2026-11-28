<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // VietQR API có xác thực (đăng ký miễn phí tại https://my.vietqr.io để lấy Client ID / API Key).
    // Không cấu hình thì dùng link ảnh nhanh img.vietqr.io (không cần khóa).
    'vietqr' => [
        'client_id' => env('VIETQR_CLIENT_ID'),
        'api_key' => env('VIETQR_API_KEY'),
        'generate_url' => 'https://api.vietqr.io/v2/generate',
    ],

    // Zalo ZNS: gửi tin xác nhận / nhắc / hủy lịch tới SĐT khách qua Official Account của tiệm.
    // Các bước lấy khóa và đăng ký mẫu tin: docs/integrations/zalo-zns.md
    'zalo' => [
        'driver' => env('ZALO_DRIVER', 'log'), // log | zns
        'mode' => env('ZALO_ZNS_MODE'), // để trống khi app đã duyệt; "development" khi chỉ gửi thử tới quản trị viên
        'app_id' => env('ZALO_APP_ID'),
        'secret_key' => env('ZALO_SECRET_KEY'),
        'access_token' => env('ZALO_ACCESS_TOKEN'),
        'refresh_token' => env('ZALO_REFRESH_TOKEN'),
        'templates' => [
            'confirmed' => env('ZALO_TEMPLATE_CONFIRMED'),
            'reminder' => env('ZALO_TEMPLATE_REMINDER'),
            'cancelled' => env('ZALO_TEMPLATE_CANCELLED'),
        ],
        'send_url' => 'https://business.openapi.zalo.me/message/template',
        'token_url' => 'https://oauth.zaloapp.com/v4/oa/access_token',
    ],

];
