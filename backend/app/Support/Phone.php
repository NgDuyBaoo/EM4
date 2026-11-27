<?php

namespace App\Support;

class Phone
{
    /** Chuẩn hóa SĐT Việt Nam về dạng 0xxxxxxxxx, trả về null nếu không hợp lệ. */
    public static function normalize(?string $raw): ?string
    {
        $s = preg_replace('/[\s.\-()]/', '', (string) $raw);
        if (str_starts_with($s, '+84')) {
            $s = '0'.substr($s, 3);
        } elseif (preg_match('/^84\d{9}$/', $s)) {
            $s = '0'.substr($s, 2);
        }

        return preg_match('/^0\d{9}$/', $s) ? $s : null;
    }
}
