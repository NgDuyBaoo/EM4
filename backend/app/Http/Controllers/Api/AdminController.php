<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccessCodeService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Quản lý
 */
class AdminController extends Controller
{
    /** Mã truy cập 6 số của hôm nay. */
    public function accessCode(AccessCodeService $codes): JsonResponse
    {
        $c = $codes->today();

        return response()->json(['date' => $c->code_date, 'code' => $c->code]);
    }

    /** Tạo mã truy cập mới cho hôm nay (khi mã bị lộ). */
    public function regenerateAccessCode(Request $request, AccessCodeService $codes): JsonResponse
    {
        $c = $codes->regenerate($request->user()->id);

        return response()->json(['date' => $c->code_date, 'code' => $c->code]);
    }

    /** Thống kê lượt đặt, doanh thu theo ngày, hiệu suất phòng, theo chi nhánh. */
    public function report(Request $request, ReportService $reports): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);
        $to = $data['to'] ?? now()->toDateString();
        $from = $data['from'] ?? now()->parse($to)->subDays(6)->toDateString();

        return response()->json($reports->summary($from, $to, $data['branch_id'] ?? null));
    }
}
