<?php

namespace App\Monitoring;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/** Gửi một cảnh báo tới nơi nhận đã cấu hình: email (NGOI_ALERT_MAIL hoặc chủ cửa hàng) và Slack nếu có webhook. */
class AlertChannels
{
    public static function send(BaseNotification $notification, string $slackText): void
    {
        $cfg = config('ngoi.alerts');

        if ($cfg['mail_to']) {
            Notification::route('mail', $cfg['mail_to'])->notify($notification);
        } else {
            Notification::send(User::query()->where('role', UserRole::Owner)->get(), $notification);
        }

        if ($cfg['slack_webhook']) {
            try {
                Http::timeout(5)->post($cfg['slack_webhook'], ['text' => $slackText])->throw();
            } catch (Throwable $e) {
                Log::error('Không gửi được cảnh báo lên Slack', ['error' => $e->getMessage()]);
            }
        }
    }
}
