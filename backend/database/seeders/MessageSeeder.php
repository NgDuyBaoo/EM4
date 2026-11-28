<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

/** Lời nhắn mẫu. Không dùng factory/Faker để chạy được trong image Docker production. */
class MessageSeeder extends Seeder
{
    private const SAMPLES = [
        ['Góp ý dịch vụ', 'Phòng chụp đẹp, nhân viên dễ thương. Mong tiệm có thêm phông nền màu pastel.'],
        ['Hỏi về lịch đã đặt', 'Mình đặt lịch chiều nay nhưng có thể đến trễ 10 phút, tiệm giữ chỗ giúp mình được không?'],
        ['Nhắn tin riêng', 'Nhóm mình 6 người, muốn đặt 2 phòng liền nhau vào tối thứ Bảy.'],
        ['Góp ý dịch vụ', 'Máy in ảnh hôm qua hơi mờ, tiệm kiểm tra lại giúp nhé.'],
        ['Hỏi về lịch đã đặt', 'Mình chuyển khoản rồi nhưng chưa thấy email xác nhận.'],
        ['Nhắn tin riêng', 'Tiệm có nhận chụp ảnh kỷ yếu cho lớp khoảng 30 bạn không?'],
    ];

    public function run(): void
    {
        $branchIds = Branch::pluck('id')->all();
        if (Message::exists() || $branchIds === []) {
            return;
        }

        foreach (self::SAMPLES as [$subject, $body]) {
            Message::create([
                'branch_id' => Arr::random($branchIds),
                'name' => DemoData::name(),
                'contact' => DemoData::phone(),
                'subject' => $subject,
                'body' => $body,
            ]);
        }
    }
}
