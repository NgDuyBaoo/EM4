<?php

namespace App\Services\Zalo;

use RuntimeException;

/** Zalo trả lỗi (mã error khác 0) khi gửi tin ZNS hoặc làm mới token. */
class ZaloException extends RuntimeException
{
    public function __construct(string $message, public readonly int|string $zaloCode = 0)
    {
        parent::__construct("Zalo: {$message} (mã {$zaloCode})");
    }
}
