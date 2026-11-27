<?php

namespace App\Enums;

/**
 * Vòng đời booking (docs/dac-ta-yeu-cau.md, mục 3.1):
 * booked → checked_in → completed; booked|checked_in → cancelled.
 */
enum BookingStatus: string
{
    case Booked = 'booked';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Đã đặt',
            self::CheckedIn => 'Đang chụp',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    /** Lớp Tailwind cho nhãn trạng thái */
    public function badge(): string
    {
        return match ($this) {
            self::Booked => 'bg-amber-200 text-amber-950',
            self::CheckedIn => 'bg-sky-200 text-sky-950',
            self::Completed => 'bg-stone-300 text-stone-900',
            self::Cancelled => 'bg-rose-200 text-rose-950',
        };
    }

    /** @return list<self> trạng thái có thể chuyển tới */
    public function next(): array
    {
        return match ($this) {
            self::Booked => [self::CheckedIn, self::Cancelled],
            self::CheckedIn => [self::Completed, self::Cancelled],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->next(), true);
    }

    public function isActive(): bool
    {
        return $this !== self::Cancelled;
    }
}
