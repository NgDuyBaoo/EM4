<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->booking_code,
            'date' => $this->booking_date->toDateString(),
            'time' => $this->time(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'source' => $this->source->value,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'price' => $this->price,
            'note' => $this->note,
            'cancel_reason' => $this->cancel_reason,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
