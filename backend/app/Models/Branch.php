<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'address', 'phone', 'open_time', 'close_time', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderBy('sort_order');
    }

    public function activeRooms(): HasMany
    {
        return $this->rooms()->where('is_active', true);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }

    /** Giờ mở cửa dạng 'HH:MM' */
    public function openAt(): string
    {
        return substr((string) $this->open_time, 0, 5);
    }

    public function closeAt(): string
    {
        return substr((string) $this->close_time, 0, 5);
    }

    public function mapUrl(): string
    {
        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($this->address.', Việt Nam');
    }
}
