<?php

namespace App\Http\Requests\Web;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => Phone::normalize($this->input('phone')) ?? $this->input('phone')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'họ tên', 'email' => 'email', 'phone' => 'số điện thoại', 'password' => 'mật khẩu'];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Số điện thoại cần 10 số, bắt đầu bằng 0.'];
    }
}
