<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Phòng chụp
 */
class RoomController extends Controller
{
    /**
     * Danh sách phòng, lọc theo chi nhánh.
     *
     * @unauthenticated
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['branch_id' => ['nullable', 'integer'], 'include_inactive' => ['nullable', 'boolean']]);

        return RoomResource::collection(
            Room::query()
                ->when($request->integer('branch_id'), fn ($q, $id) => $q->where('branch_id', $id))
                ->unless($request->boolean('include_inactive'), fn ($q) => $q->where('is_active', true))
                ->orderBy('branch_id')->orderBy('sort_order')
                ->get()
        );
    }

    /** Thêm phòng (chủ cửa hàng). */
    public function store(RoomRequest $request): JsonResponse
    {
        Gate::authorize('create', Room::class);

        return (new RoomResource(Room::create($request->validated())))->response()->setStatusCode(201);
    }

    /**
     * Chi tiết phòng.
     *
     * @unauthenticated
     */
    public function show(Room $room): RoomResource
    {
        return new RoomResource($room);
    }

    /** Sửa phòng: tên, phong cách, giá, bật/tắt (chủ cửa hàng). */
    public function update(RoomRequest $request, Room $room): RoomResource
    {
        Gate::authorize('update', $room);
        $room->update($request->validated());

        return new RoomResource($room);
    }

    /** Xóa phòng (xóa mềm, lịch sử booking vẫn giữ) – chủ cửa hàng. */
    public function destroy(Room $room): JsonResponse
    {
        Gate::authorize('delete', $room);
        $room->delete();

        return response()->json(null, 204);
    }
}
