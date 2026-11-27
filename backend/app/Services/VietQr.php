<?php

namespace App\Services;

/**
 * Tích hợp VietQR: tạo ảnh mã QR chuyển khoản theo chuẩn NAPAS cho đúng số tiền và nội dung là mã booking.
 * Dùng Quick Link API của VietQR (https://www.vietqr.io/danh-sach-api/link-tao-ma-nhanh).
 */
class VietQr
{
    public static function imageUrl(int $amount, string $bookingCode): string
    {
        $cfg = config('ngoi.vietqr');

        return sprintf(
            'https://img.vietqr.io/image/%s-%s-compact2.png?%s',
            $cfg['bank_bin'],
            $cfg['account'],
            http_build_query(['amount' => $amount, 'addInfo' => $bookingCode, 'accountName' => $cfg['account_name']]),
        );
    }
}
