<?php

namespace App\Support;

/**
 * Đọc số liệu máy chủ Linux (container Docker của dự án chạy Linux).
 * Tách riêng để test có thể thay bằng số liệu giả.
 */
class ServerStats
{
    /** Tổng byte nhận/gửi qua mọi card mạng (trừ loopback) từ /proc/net/dev. @return array{rx:int, tx:int}|null */
    public function network(): ?array
    {
        $lines = @file('/proc/net/dev', FILE_IGNORE_NEW_LINES);
        if (! $lines) {
            return null;
        }

        $rx = $tx = 0;
        foreach (array_slice($lines, 2) as $line) {
            [$iface, $data] = array_map('trim', explode(':', $line, 2)) + [1 => ''];
            if ($iface === 'lo' || $data === '') {
                continue;
            }
            $cols = preg_split('/\s+/', $data);
            $rx += (int) $cols[0];
            $tx += (int) $cols[8];
        }

        return ['rx' => $rx, 'tx' => $tx];
    }

    /** % CPU ước lượng từ load trung bình 1 phút / số nhân. */
    public function cpuPercent(): ?float
    {
        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
        if ($load === false) {
            return null;
        }
        $cores = max(1, (int) @shell_exec('nproc 2>/dev/null') ?: 1);

        return round(min(100, $load[0] / $cores * 100), 1);
    }

    /** % RAM đã dùng từ /proc/meminfo (MemAvailable). */
    public function memoryPercent(): ?float
    {
        $info = @file_get_contents('/proc/meminfo');
        if (! $info || ! preg_match('/MemTotal:\s+(\d+)/', $info, $t) || ! preg_match('/MemAvailable:\s+(\d+)/', $info, $a)) {
            return null;
        }

        return round((1 - $a[1] / $t[1]) * 100, 1);
    }

    /** % đĩa đã dùng của thư mục. */
    public function diskPercent(string $directory = '/'): ?float
    {
        $total = @disk_total_space($directory);
        $free = @disk_free_space($directory);

        return $total ? round((1 - $free / $total) * 100, 1) : null;
    }
}
