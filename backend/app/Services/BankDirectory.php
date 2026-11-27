<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tích hợp API bên ngoài: danh sách ngân hàng của VietQR (https://api.vietqr.io/v2/banks).
 * Dùng để hiện tên và logo ngân hàng nhận tiền cạnh mã QR chuyển khoản.
 * Kết quả cache 1 ngày; API lỗi thì trả null, trang vẫn hiện mã QR bình thường.
 */
class BankDirectory
{
    /** @return array{bin:string, name:string, short_name:string, code:string, logo:?string}|null */
    public function find(string $bin): ?array
    {
        return $this->all()[$bin] ?? null;
    }

    /** Ngân hàng nhận tiền của cửa hàng (theo NGOI_BANK_BIN) kèm số tài khoản. */
    public function shopAccount(): ?array
    {
        $cfg = config('ngoi.vietqr');
        $bank = $this->find((string) $cfg['bank_bin']);

        return $bank ? $bank + ['account' => $cfg['account'], 'account_name' => $cfg['account_name']] : null;
    }

    /** @return array<string, array{bin:string, name:string, short_name:string, code:string, logo:?string}> */
    public function all(): array
    {
        $cached = Cache::get('vietqr:banks');
        if ($cached !== null) {
            return $cached;
        }

        try {
            $res = Http::timeout(5)->retry(2, 200)->acceptJson()->get(config('ngoi.vietqr.banks_url'))->throw()->json();
            if (data_get($res, 'code') !== '00') {
                throw new \RuntimeException('VietQR trả mã '.data_get($res, 'code').': '.data_get($res, 'desc'));
            }

            $banks = collect(data_get($res, 'data', []))
                ->filter(fn ($b) => ! empty($b['bin']))
                ->mapWithKeys(fn ($b) => [(string) $b['bin'] => [
                    'bin' => (string) $b['bin'],
                    'name' => (string) ($b['name'] ?? ''),
                    'short_name' => (string) ($b['shortName'] ?? $b['short_name'] ?? $b['code'] ?? ''),
                    'code' => (string) ($b['code'] ?? ''),
                    'logo' => $b['logo'] ?? null,
                ]])
                ->all();

            Cache::put('vietqr:banks', $banks, now()->addDay());

            return $banks;
        } catch (Throwable $e) {
            Log::warning('Không lấy được danh sách ngân hàng từ VietQR', ['error' => $e->getMessage()]);
            Cache::put('vietqr:banks', [], now()->addMinutes(10)); // tránh gọi lại liên tục khi API lỗi

            return [];
        }
    }
}
