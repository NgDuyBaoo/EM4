<?php

namespace App\Jobs;

use App\Monitoring\AlertChannels;
use App\Notifications\ErrorOccurred;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Gửi cảnh báo lỗi nghiêm trọng qua email và Slack (chạy trong queue để không làm chậm thêm request đang lỗi). */
class SendErrorAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public string $exception,
        public string $message,
        public string $file,
        public int $line,
        public string $where,
    ) {}

    public function handle(): void
    {
        $notification = new ErrorOccurred($this->exception, $this->message, $this->file, $this->line, $this->where);
        AlertChannels::send($notification, $notification->summary());
    }
}
