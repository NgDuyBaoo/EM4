<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Room */
class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'style' => $this->style,
            'description' => $this->description,
            'price_per_slot' => $this->price_per_slot,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
