<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'contact' => ['required', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['contact' => 'điện thoại / email', 'body' => 'lời nhắn', 'branch_id' => 'chi nhánh'];
    }
}
