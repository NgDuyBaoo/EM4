<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Branch */
class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'open_time' => $this->openAt(),
            'close_time' => $this->closeAt(),
            'is_active' => $this->is_active,
            'map_url' => $this->mapUrl(),
            'rooms_count' => $this->whenCounted('activeRooms'),
            'rooms' => RoomResource::collection($this->whenLoaded('activeRooms')),
        ];
    }
}
