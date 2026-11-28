<?php

namespace App\Notifications\Messages;

/** Một tin ZNS: loại mẫu (khóa trong config services.zalo.templates) và tham số của mẫu. */
class ZaloMessage
{
    /** @param array<string, string> $data */
    public function __construct(
        public string $template,
        public array $data,
        public string $trackingId,
    ) {}
}
