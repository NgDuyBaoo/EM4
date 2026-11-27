<?php

namespace App\Services;

use App\Models\AccessCode;
use App\Support\SlotClock;
use Illuminate\Support\Facades\Cache;

class AccessCodeService
{
    /** Mã của hôm nay (giờ VN). Chưa có thì sinh ngẫu nhiên; insertOrIgnore để 2 request không tạo 2 mã. */
    public function today(): AccessCode
    {
        $date = SlotClock::today();

        return Cache::remember("access-code:{$date}", now()->endOfDay(), function () use ($date) {
            AccessCode::query()->insertOrIgnore([
                'code_date' => $date, 'code' => $this->random(), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return AccessCode::query()->findOrFail($date);
        });
    }

    public function regenerate(?int $userId = null): AccessCode
    {
        $date = SlotClock::today();
        AccessCode::query()->updateOrCreate(['code_date' => $date], ['code' => $this->random(), 'created_by' => $userId]);
        Cache::forget("access-code:{$date}");

        return $this->today();
    }

    public function verify(?string $input): bool
    {
        $input = trim((string) $input);

        return (bool) preg_match('/^\d{6}$/', $input) && hash_equals($this->today()->code, $input);
    }

    private function random(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
