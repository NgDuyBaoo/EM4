<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\SlowQueryDetected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Gửi cảnh báo truy vấn chậm qua email (chủ tiệm hoặc NGOI_ALERT_MAIL) và Slack webhook nếu có. */
class SendSlowQueryAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $kind, public string $sql, public float $ms, public ?string $url = null) {}

    public function handle(): void
    {
        $cfg = config('ngoi.slow_query_alert');
        $notification = new SlowQueryDetected($this->kind, $this->sql, $this->ms, $this->url);

        if ($cfg['mail_to']) {
            Notification::route('mail', $cfg['mail_to'])->notify($notification);
        } else {
            Notification::send(User::query()->where('role', UserRole::Owner)->get(), $notification);
        }

        if ($cfg['slack_webhook']) {
            try {
                Http::timeout(5)->post($cfg['slack_webhook'], ['text' => $notification->summary()])->throw();
            } catch (Throwable $e) {
                Log::channel('slow-query')->error('Không gửi được cảnh báo lên Slack', ['error' => $e->getMessage()]);
            }
        }
    }
}
