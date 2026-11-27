<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $id = $this->route('branch')?->id;

        return [
            'code' => [$creating ? 'required' : 'sometimes', 'alpha_dash', 'max:20', Rule::unique('branches', 'code')->ignore($id)],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:120'],
            'address' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'open_time' => ['sometimes', 'date_format:H:i'],
            'close_time' => ['sometimes', 'date_format:H:i', 'after:open_time'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
