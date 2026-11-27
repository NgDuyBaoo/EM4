<?php

namespace App\Models;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code', 'user_id', 'branch_id', 'room_id', 'booking_date', 'slot_time',
        'customer_name', 'customer_phone', 'source', 'status', 'price', 'note',
        'checked_in_at', 'completed_at', 'cancelled_at', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date:Y-m-d',
            'status' => BookingStatus::class,
            'source' => BookingSource::class,
            'price' => 'integer',
            'checked_in_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class)->withTrashed();
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', '!=', BookingStatus::Cancelled->value);
    }

    public function scopeOnDate(Builder $q, string $date): Builder
    {
        return $q->whereDate('booking_date', $date);
    }

    /** 'HH:MM' */
    public function time(): string
    {
        return substr((string) $this->slot_time, 0, 5);
    }
}
