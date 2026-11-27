<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // quyền kiểm tra bằng RoomPolicy trong controller
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'branch_id' => [$creating ? 'required' : 'prohibited', 'integer', 'exists:branches,id'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:80'],
            'style' => ['sometimes', 'nullable', 'string', 'max:80'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'price_per_slot' => [$creating ? 'required' : 'sometimes', 'integer', 'min:0', 'max:10000000', 'multiple_of:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'tên phòng', 'price_per_slot' => 'giá mỗi slot', 'branch_id' => 'chi nhánh'];
    }
}
