<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Services\AvailabilityService;
use App\Services\AccessCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Chi nhánh
 */
class BranchController extends Controller
{
    /**
     * Danh sách chi nhánh đang hoạt động (kèm phòng). Cache 10 phút.
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        $branches = Cache::remember('catalog:branches', 600, fn () => Branch::active()
            ->with('activeRooms')            // eager loading: 2 truy vấn cho mọi chi nhánh
            ->withCount('activeRooms')
            ->get());

        return BranchResource::collection($branches);
    }

    /** Tạo chi nhánh (chủ cửa hàng). */
    public function store(BranchRequest $request): JsonResponse
    {
        Gate::authorize('create', Branch::class);
        $branch = Branch::create($request->validated());

        return (new BranchResource($branch))->response()->setStatusCode(201);
    }

    /**
     * Chi tiết chi nhánh.
     *
     * @unauthenticated
     */
    public function show(Branch $branch): BranchResource
    {
        return new BranchResource($branch->load('activeRooms')->loadCount('activeRooms'));
    }

    /** Sửa chi nhánh (chủ cửa hàng). */
    public function update(BranchRequest $request, Branch $branch): BranchResource
    {
        Gate::authorize('update', $branch);
        $branch->update($request->validated());

        return new BranchResource($branch);
    }

    /** Xóa chi nhánh (chủ cửa hàng). Chi nhánh đã có booking thì chỉ được tắt hoạt động. */
    public function destroy(Branch $branch): JsonResponse
    {
        Gate::authorize('delete', $branch);
        if ($branch->bookings()->exists()) {
            return response()->json(['message' => 'Chi nhánh đã có booking, hãy tắt hoạt động thay vì xóa.', 'code' => 'HAS_BOOKINGS'], 409);
        }
        $branch->rooms()->withTrashed()->forceDelete();
        $branch->delete();

        return response()->json(null, 204);
    }

    /**
     * Lịch trống hôm nay của chi nhánh. Khách cần gửi mã truy cập 6 số qua header X-Access-Code.
     */
    public function availability(Request $request, Branch $branch, AvailabilityService $availability, AccessCodeService $codes): JsonResponse
    {
        if (! $request->user()->isStaff() && ! $codes->verify($request->header('X-Access-Code'))) {
            return response()->json(['message' => 'Mã truy cập chưa đúng hoặc đã hết hạn.', 'code' => 'INVALID_ACCESS_CODE'], 403);
        }
        $date = $request->user()->isStaff() ? $request->date('date')?->toDateString() : null;

        return response()->json($availability->forBranch($branch, $date));
    }
}
