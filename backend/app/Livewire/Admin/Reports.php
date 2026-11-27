<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Services\ReportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Thống kê & báo cáo. #[Lazy]: trang hiện khung chờ ngay, số liệu tải sau để không chặn trang. */
#[Lazy]
#[Layout('layouts.admin')]
#[Title('Thống kê – Quản lý Ngói')]
class Reports extends Component
{
    #[Url]
    public int $days = 7;

    #[Url]
    public ?int $branchId = null;

    public function placeholder()
    {
        return view('livewire.admin.reports-placeholder');
    }

    private function data(): array
    {
        $to = now()->toDateString();
        $from = now()->subDays(max(1, min($this->days, 366)) - 1)->toDateString();

        return app(ReportService::class)->summary($from, $to, $this->branchId);
    }

    /** Xuất CSV (mở được bằng Excel). */
    public function export(): StreamedResponse
    {
        $d = $this->data();

        return response()->streamDownload(function () use ($d) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ngày', 'Lượt đặt', 'Hoàn thành', 'Đã hủy', 'Doanh thu (đ)']);
            foreach ($d['days'] as $r) {
                fputcsv($out, [$r['date'], $r['bookings'], $r['completed'], $r['cancelled'], $r['revenue']]);
            }
            fclose($out);
        }, "ngoi-bao-cao-{$d['from']}-{$d['to']}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.admin.reports', [
            'r' => $this->data(),
            'branches' => Branch::orderBy('sort_order')->get(['id', 'name']),
        ]);
    }
}
