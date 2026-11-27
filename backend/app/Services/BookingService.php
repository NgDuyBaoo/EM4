<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Jobs\SendBookingConfirmation;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\User;
use App\Support\Phone;
use App\Support\SlotClock;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BookingService
{
    private const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Tạo booking: 1 dòng / slot, chung 1 mã. Kiểm tra BR-03..BR-11 trong 1 giao dịch.
     *
     * @param  array<int, array{room_id:int, time:string}>  $items
     * @return Collection<int, Booking>
     */
    public function create(
        Branch $branch,
        array $items,
        string $customerName,
        string $customerPhone,
        BookingSource $source = BookingSource::Online,
        ?User $user = null,
        string $note = '',
    ): Collection {
        $phone = Phone::normalize($customerPhone)
            ?? throw new BookingException('INVALID_PHONE', 'Số điện thoại không hợp lệ: cần 10 số bắt đầu bằng 0.', 422);
        if ($items === []) {
            throw new BookingException('NO_SLOT', 'Bạn chưa chọn khung giờ nào.', 422);
        }
        $max = (int) config('ngoi.max_slots_per_booking');
        if ($source === BookingSource::Online && count($items) > $max) {
            throw new BookingException('TOO_MANY_SLOTS', "Mỗi lần đặt tối đa {$max} slot.", 422);
        }
        if (! $branch->is_active) {
            throw new BookingException('BRANCH_CLOSED', 'Chi nhánh tạm ngừng nhận đặt lịch.', 422);
        }

        $date = SlotClock::today(); // BR-03: chỉ đặt trong ngày hiện tại
        $rooms = $branch->activeRooms()->get()->keyBy('id');
        $seen = [];
        $rows = [];
        foreach ($items as $item) {
            $room = $rooms->get((int) ($item['room_id'] ?? 0))
                ?? throw new BookingException('INVALID_ROOM', 'Phòng không thuộc chi nhánh này hoặc đang tạm ngừng.', 422);
            $time = (string) ($item['time'] ?? '');
            if (! SlotClock::isValidSlot($time, $branch->openAt(), $branch->closeAt())) {
                throw new BookingException('INVALID_SLOT', "Giờ {$time} không nằm trong khung {$branch->openAt()}–{$branch->closeAt()} (mỗi slot 10 phút).", 422);
            }
            $key = $room->id.'|'.$time;
            if (isset($seen[$key])) {
                throw new BookingException('DUPLICATE_SLOT', 'Có khung giờ bị chọn hai lần.', 422);
            }
            $seen[$key] = true;
            // BR-08 / BR-09: online không đặt slot đã bắt đầu, walk-in được đặt slot chưa kết thúc.
            $late = $source === BookingSource::Online ? SlotClock::started($time) : SlotClock::ended($time);
            if ($late) {
                throw BookingException::slotPast($time, $room->id);
            }
            $rows[] = [$room, $time];
        }

        $code = $this->newCode();
        $bookings = DB::transaction(function () use ($rows, $code, $user, $branch, $date, $customerName, $phone, $source, $note) {
            $created = collect();
            foreach ($rows as [$room, $time]) {
                try {
                    $created->push(Booking::create([
                        'booking_code' => $code,
                        'user_id' => $user?->id,
                        'branch_id' => $branch->id,
                        'room_id' => $room->id,
                        'booking_date' => $date,
                        'slot_time' => $time.':00',
                        'customer_name' => trim($customerName),
                        'customer_phone' => $phone,
                        'source' => $source,
                        'status' => BookingStatus::Booked,
                        'price' => $room->price_per_slot,
                        'note' => $note,
                    ]));
                } catch (UniqueConstraintViolationException) {
                    // Chỉ số UNIQUE (room_id, booking_date, slot_time, active_slot) chặn đặt trùng (BR-06).
                    throw BookingException::slotTaken($room->name, $time, $room->id);
                }
            }

            return $created;
        });

        Log::channel('booking')->info('Tạo booking', ['code' => $code, 'branch' => $branch->code, 'slots' => count($rows), 'source' => $source->value]);
        if ($user?->email) {
            SendBookingConfirmation::dispatch($code, $user->id); // gửi email xác nhận qua queue
        }

        return $bookings->each->load(['room', 'branch']);
    }

    /** Admin/nhân viên đổi trạng thái theo vòng đời. */
    public function transition(Booking $booking, BookingStatus $to, string $reason = ''): Booking
    {
        return DB::transaction(function () use ($booking, $to, $reason) {
            $fresh = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if (! $fresh->status->canTransitionTo($to)) {
                throw new BookingException('INVALID_TRANSITION', "Không thể chuyển từ \"{$fresh->status->label()}\" sang \"{$to->label()}\".", 409);
            }
            $fresh->status = $to;
            match ($to) {
                BookingStatus::CheckedIn => $fresh->checked_in_at = now(),
                BookingStatus::Completed => $fresh->completed_at = now(),
                BookingStatus::Cancelled => [$fresh->cancelled_at = now(), $fresh->cancel_reason = $reason ?: 'Nhân viên hủy'],
                default => null,
            };
            $fresh->save();
            Log::channel('booking')->info('Đổi trạng thái', ['id' => $fresh->id, 'to' => $to->value]);

            return $fresh->load(['room', 'branch']);
        });
    }

    /** Khách tự hủy: chỉ slot còn "Đã đặt" và chưa bắt đầu (BR-10). */
    public function customerCancel(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::Booked || SlotClock::started($booking->time(), $booking->booking_date->toDateString())) {
            throw new BookingException('NOT_CANCELLABLE', 'Không thể hủy: lịch đã qua giờ hoặc đã check-in.', 409);
        }

        return $this->transition($booking, BookingStatus::Cancelled, 'Khách tự hủy');
    }

    /** Hủy các booking quá giờ hẹn mà khách chưa đến (BR-07). Trả về số booking đã hủy. */
    public function cancelNoShows(): int
    {
        $grace = (int) config('ngoi.no_show_grace_minutes');
        $cutoff = now()->subMinutes($grace);
        if ($cutoff->toDateString() !== SlotClock::today()) {
            return 0;
        }

        return Booking::query()
            ->onDate(SlotClock::today())
            ->where('status', BookingStatus::Booked)
            ->where('slot_time', '<=', $cutoff->format('H:i:s'))
            ->get()
            ->each(fn (Booking $b) => $this->transition($b, BookingStatus::Cancelled, "Tự hủy: quá {$grace} phút chưa check-in"))
            ->count();
    }

    private function newCode(): string
    {
        do {
            $code = 'NG';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (Booking::query()->where('booking_code', $code)->exists());

        return $code;
    }
}
