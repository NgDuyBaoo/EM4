# Thông báo khách qua Zalo ZNS

Khách **không nhận email**. Mọi thông báo gửi khách đi qua **Zalo ZNS** (Zalo Notification Service) bằng Zalo Official Account (OA) của tiệm, tới số điện thoại khách nhập khi đặt lịch (`bookings.customer_phone`).

Cảnh báo vận hành gửi chủ tiệm (lỗi 5xx, truy vấn chậm, máy chủ quá tải) **không đổi**, vẫn đi qua email / Slack như trong [giam-sat.md](../giam-sat.md).

## Khi nào khách nhận tin

| Sự kiện | Mẫu | Gửi khi | Không gửi khi |
|---|---|---|---|
| Xác nhận lịch | `confirmed` | Khách đặt online thành công (web hoặc API) | Đặt tại quầy (walk-in) |
| Lịch bị hủy | `cancelled` | Nhân viên hủy, hoặc hệ thống tự hủy vì quá giờ chưa check-in (1 tin / mã booking) | Khách tự hủy trên web (đã thấy kết quả ngay) |
| Nhắc giờ chụp | `reminder` | `NGOI_REMINDER_MINUTES` phút trước slot sớm nhất của mã booking (mặc định 30). Lệnh `bookings:send-reminders` chạy mỗi phút, mỗi mã chỉ nhắc 1 lần (cột `reminded_at`) | Khách đặt sát giờ, trong khoảng nhắc (tin xác nhận là đủ); walk-in |

Tin gửi qua queue (`App\Jobs\SendBookingZalo`, thử lại 3 lần sau 10s / 60s). Hết lượt thử thì ghi lỗi vào `storage/logs/zalo-*.log`.

## Code

| File | Vai trò |
|---|---|
| `app/Services/Zalo/ZaloZns.php` | Gọi API ZNS, tự làm mới access token bằng refresh token |
| `app/Notifications/Channels/ZaloChannel.php` | Kênh thông báo Laravel `Notification::route('zalo', $sdt)` |
| `app/Notifications/BookingConfirmed.php`, `BookingReminder.php`, `BookingCancelled.php` | Tham số của từng mẫu tin |
| `app/Console/Commands/SendBookingReminders.php` | Lệnh nhắc lịch, đăng ký trong `routes/console.php` |
| `tests/Feature/ZaloNotificationTest.php` | Test bằng `Http::fake`, không gọi Zalo thật |

## Chạy khi chưa có OA

Để `ZALO_DRIVER=log` (mặc định). Hệ thống vẫn chạy đủ luồng nhưng chỉ ghi nội dung tin (SĐT, mẫu, tham số) vào `storage/logs/zalo-*.log`, không gọi Zalo.

## Việc chủ tiệm phải tự làm để gửi thật

Các bước dưới đây cần tài khoản và giấy tờ của tiệm nên phải tự làm. Repo **không** chứa sẵn template_id hay token nào. Giao diện Zalo có thể đổi, khi khác thì làm theo tài liệu chính thức: https://developers.zalo.me/docs/zalo-notification-service

1. **Tạo Zalo Official Account** cho tiệm tại https://oa.zalo.me và **xác thực OA** (ZNS chỉ dùng được với OA đã xác thực).
2. **Tạo ứng dụng** trên https://developers.zalo.me, ghi lại **App ID** và **Secret Key** (*Cài đặt ứng dụng*). Liên kết ứng dụng với OA ở mục *Official Account* của ứng dụng.
3. **Đăng ký dịch vụ ZNS** và nạp tiền trên Zalo Cloud Account (https://account.zalo.cloud), chọn OA của tiệm. ZNS tính phí theo từng tin gửi thành công.
4. **Tạo 3 mẫu tin ZNS** và chờ Zalo duyệt. Khi tạo mẫu, đặt **tên tham số đúng như bảng dưới** (code gửi đúng các tên này). Mẫu được duyệt sẽ có **template_id**.
5. **Lấy access token và refresh token của OA**: trong ứng dụng, dùng luồng *Official Account API → cấp quyền OA (OAuth v4)* hoặc công cụ *API Explorer* của Zalo Developers. Access token sống khoảng 25 giờ; refresh token dùng 1 lần và sống khoảng 3 tháng.
6. Điền vào `.env` trên máy chủ rồi chạy `php artisan config:cache`:

```dotenv
ZALO_DRIVER=zns
ZALO_APP_ID=...            # bước 2
ZALO_SECRET_KEY=...        # bước 2
ZALO_ACCESS_TOKEN=...      # bước 5 (có thể để trống, hệ thống tự lấy bằng refresh token)
ZALO_REFRESH_TOKEN=...     # bước 5
ZALO_TEMPLATE_CONFIRMED=...  # template_id mẫu xác nhận
ZALO_TEMPLATE_REMINDER=...   # template_id mẫu nhắc lịch
ZALO_TEMPLATE_CANCELLED=...  # template_id mẫu báo hủy
NGOI_REMINDER_MINUTES=30
# Khi ứng dụng chưa được duyệt: chỉ gửi thử được tới quản trị viên OA/ứng dụng
# ZALO_ZNS_MODE=development
```

**Lưu ý về token:** mỗi lần làm mới, Zalo trả cặp token mới và refresh token cũ hết hiệu lực. Hệ thống lưu cặp mới vào cache (khóa `zalo:access_token`, `zalo:refresh_token`), vì vậy `CACHE_STORE` phải là store bền (`database` hoặc `redis`, không dùng `array`). `ZALO_REFRESH_TOKEN` trong `.env` chỉ dùng lần đầu. Nếu xóa cache hoặc để quá 3 tháng không gửi tin, phải lấy token mới ở bước 5. Mẫu nào chưa có template_id thì tin đó được bỏ qua và ghi cảnh báo vào log.

Máy chủ cần chạy `queue:work` (gửi tin) và `schedule:work` (nhắc lịch, tự hủy). Docker Compose đã có sẵn 2 service `queue` và `scheduler`.

## Tham số của mẫu tin

Tên tham số phải trùng khi đăng ký mẫu. Mẫu có thể không dùng hết các tham số.

| Tham số | Ví dụ | Có ở mẫu |
|---|---|---|
| `customer_name` | Nguyễn Thị Lan (tối đa 30 ký tự) | cả 3 |
| `booking_code` | NGAB12CD | cả 3 |
| `branch_name` | 36 Nguyễn Sinh Cung | cả 3 |
| `booking_date` | 16/10/2026 | cả 3 |
| `slot_time` | 14:30 (giờ bắt đầu sớm nhất) | cả 3 |
| `slots` | 14:30 Phòng 1 · Vintage, 14:40 Phòng 1 · Vintage | cả 3 |
| `total` | 100000 (đồng) | `confirmed` |
| `cancel_reason` | Tự hủy: quá 15 phút chưa check-in | `cancelled` |

Gợi ý nội dung mẫu (Zalo duyệt theo quy định của họ, có thể yêu cầu sửa):

- **Xác nhận:** "Ngói Photobooth xác nhận lịch chụp của <customer_name>. Mã: <booking_code>. Chi nhánh: <branch_name>. Ngày <booking_date>, bắt đầu <slot_time>. Chi tiết: <slots>. Tổng tiền: <total>đ. Vui lòng đến đúng giờ."
- **Nhắc lịch:** "<customer_name> ơi, lịch chụp <booking_code> tại <branch_name> bắt đầu lúc <slot_time> hôm nay (<booking_date>). Ngói hẹn gặp bạn!"
- **Báo hủy:** "Lịch chụp <booking_code> của <customer_name> ngày <booking_date> (<slots>) đã bị hủy. Lý do: <cancel_reason>."

## API Zalo đang gọi

| API | Dùng để | Xác thực |
|---|---|---|
| `POST https://business.openapi.zalo.me/message/template` | Gửi tin ZNS: `phone` (84xxxxxxxxx), `template_id`, `template_data`, `tracking_id` | Header `access_token` |
| `POST https://oauth.zaloapp.com/v4/oa/access_token` | Đổi refresh token lấy access token mới (`grant_type=refresh_token`, `app_id`) | Header `secret_key` |
