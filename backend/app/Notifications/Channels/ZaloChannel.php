<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\ZaloMessage;
use App\Services\Zalo\ZaloZns;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Kênh thông báo "zalo": gửi tin ZNS tới số điện thoại của khách.
 * Dùng: Notification::route('zalo', '0912345678')->notify(new BookingConfirmed(...)).
 */
class ZaloChannel
{
    public function __construct(private ZaloZns $zns) {}

    public function send(object $notifiable, Notification $notification): ?string
    {
        $phone = $notifiable->routeNotificationFor('zalo', $notification);
        if (! $phone) {
            return null;
        }

        /** @var ZaloMessage $message */
        $message = $notification->toZalo($notifiable);
        $templateId = (string) config("services.zalo.templates.{$message->template}");
        if ($templateId === '' && config('services.zalo.driver') === 'zns') {
            Log::channel('zalo')->warning("Chưa cấu hình template_id cho mẫu \"{$message->template}\", bỏ qua", ['tracking_id' => $message->trackingId]);

            return null;
        }

        return $this->zns->send($phone, $templateId ?: $message->template, $message->data, $message->trackingId);
    }
}
