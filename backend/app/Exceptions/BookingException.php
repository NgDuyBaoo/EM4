<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/** Lỗi nghiệp vụ khi đặt / đổi trạng thái booking. Trả JSON 409/422 với mã lỗi rõ ràng. */
class BookingException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 409,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function slotTaken(string $room, string $time, int $roomId): self
    {
        return new self('SLOT_TAKEN', "{$room} lúc {$time} vừa có người đặt. Vui lòng chọn giờ khác.", 409, ['room_id' => $roomId, 'time' => $time]);
    }

    public static function slotPast(string $time, int $roomId): self
    {
        return new self('SLOT_PAST', "Khung {$time} đã qua. Vui lòng chọn giờ khác.", 409, ['room_id' => $roomId, 'time' => $time]);
    }

    public function report(): void
    {
        Log::channel('booking')->info('Từ chối booking', ['code' => $this->errorCode, 'message' => $this->getMessage(), 'details' => $this->details]);
    }

    public function render($request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null; // trang web: để Livewire/controller tự hiển thị lỗi
        }

        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'details' => $this->details,
        ], $this->status);
    }
}
