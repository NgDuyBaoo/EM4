<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isStaff = (bool) $this->user()?->isStaff();

        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            // Khách phải nhập mã truy cập 6 số của hôm nay. Nhân viên đặt tại quầy thì không cần.
            'access_code' => [$isStaff ? 'nullable' : 'required', 'digits:6'],
            'customer_name' => ['required', 'string', 'max:80'],
            'customer_phone' => ['required', 'string', 'regex:/^(\+?84|0)[\d\s.\-]{9,12}$/'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:'.($isStaff ? 50 : config('ngoi.max_slots_per_booking'))],
            'items.*.room_id' => ['required', 'integer'],
            'items.*.time' => ['required', 'date_format:H:i'],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_id' => 'chi nhánh', 'access_code' => 'mã truy cập', 'customer_name' => 'tên khách',
            'customer_phone' => 'số điện thoại', 'items' => 'khung giờ', 'items.*.time' => 'giờ', 'items.*.room_id' => 'phòng',
        ];
    }

    public function messages(): array
    {
        return ['customer_phone.regex' => 'Số điện thoại không hợp lệ: cần 10 số bắt đầu bằng 0.'];
    }
}
