<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Branch;
use App\Services\AccessCodeService;
use App\Services\BookingService;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * @tags Booking
 */
class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    /**
     * Danh sách booking. Khách: chỉ booking của mình. Nhân viên: lọc theo chi nhánh, ngày, trạng thái, tìm kiếm.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'q' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $user = $request->user();

        $query = Booking::query()
            ->with(['room', 'branch'])  // eager loading tránh N+1
            ->unless($user->isStaff(), fn ($q) => $q->where('user_id', $user->id))
            ->when($filters['branch_id'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($filters['date'] ?? null, fn ($q, $v) => $q->onDate($v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('customer_name', 'like', "%{$v}%")
                ->orWhere('customer_phone', 'like', "%{$v}%")
                ->orWhere('booking_code', 'like', "%{$v}%")))
            ->orderByDesc('booking_date')->orderBy('slot_time');

        return BookingResource::collection($query->paginate($filters['per_page'] ?? 20)->withQueryString());
    }

    /**
     * Đặt lịch. Khách: cần mã truy cập hôm nay, chỉ đặt slot chưa bắt đầu. Nhân viên: đặt tại quầy (walk-in).
     *
     * Trả 409 SLOT_TAKEN nếu slot vừa có người đặt, 409 SLOT_PAST nếu slot đã qua.
     */
    public function store(StoreBookingRequest $request, AccessCodeService $codes): JsonResponse
    {
        $user = $request->user();
        $staff = $user->isStaff() && $user->tokenCan('staff');
        if (! $staff && ! $codes->verify($request->input('access_code'))) {
            throw new BookingException('INVALID_ACCESS_CODE', 'Mã truy cập chưa đúng. Kiểm tra lại mã 6 số hôm nay tại quầy Ngói.', 422);
        }

        $created = $this->bookings->create(
            Branch::findOrFail($request->integer('branch_id')),
            $request->input('items'),
            $request->input('customer_name'),
            $request->input('customer_phone'),
            $staff ? BookingSource::Walkin : BookingSource::Online,
            $staff ? null : $user,
            (string) $request->input('note', ''),
        );

        return response()->json([
            'code' => $created->first()->booking_code,
            'total' => $created->sum('price'),
            'data' => BookingResource::collection($created),
        ], 201);
    }

    /** Chi tiết 1 booking. */
    public function show(Booking $booking): BookingResource
    {
        Gate::authorize('view', $booking);

        return new BookingResource($booking->load(['room', 'branch']));
    }

    /**
     * Cập nhật booking: đổi trạng thái bằng action (checkin | complete | cancel) hoặc sửa thông tin khách (nhân viên).
     * Khách chỉ được action=cancel trước giờ chụp.
     */
    public function update(UpdateBookingRequest $request, Booking $booking): BookingResource
    {
        $user = $request->user();
        if ($action = $request->input('action')) {
            $booking = $user->isStaff()
                ? $this->bookings->transition($booking, match ($action) {
                    'checkin' => BookingStatus::CheckedIn,
                    'complete' => BookingStatus::Completed,
                    'cancel' => BookingStatus::Cancelled,
                }, (string) $request->input('reason', ''))
                : $this->bookings->customerCancel($booking);
        }
        if ($user->isStaff()) {
            $data = $request->safe()->only(['customer_name', 'customer_phone', 'note']);
            if (isset($data['customer_phone'])) {
                $data['customer_phone'] = Phone::normalize($data['customer_phone']) ?? $booking->customer_phone;
            }
            $booking->fill(array_filter($data, fn ($v) => $v !== null))->save();
        }

        return new BookingResource($booking->load(['room', 'branch']));
    }

    /** Xóa hẳn booking (chủ cửa hàng, dùng khi nhập nhầm). */
    public function destroy(Booking $booking): JsonResponse
    {
        Gate::authorize('delete', $booking);
        $booking->delete();

        return response()->json(null, 204);
    }
}
