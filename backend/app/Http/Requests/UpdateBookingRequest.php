<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('booking'));
    }

    public function rules(): array
    {
        $staff = (bool) $this->user()?->isStaff();

        return [
            // Khách chỉ được "cancel". Nhân viên: checkin / complete / cancel và sửa thông tin khách.
            'action' => ['sometimes', Rule::in($staff ? ['checkin', 'complete', 'cancel'] : ['cancel'])],
            'reason' => ['nullable', 'string', 'max:255'],
            'customer_name' => [Rule::prohibitedIf(! $staff), 'sometimes', 'string', 'max:80'],
            'customer_phone' => [Rule::prohibitedIf(! $staff), 'sometimes', 'string', 'regex:/^(\+?84|0)[\d\s.\-]{9,12}$/'],
            'note' => [Rule::prohibitedIf(! $staff), 'sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
