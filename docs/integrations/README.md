# Bằng chứng gọi thật API bên ngoài

Lệnh `php artisan ngoi:check-integrations` gọi thật từng API (bỏ qua cache) và in kết quả. Lệnh chạy:

- trên **GitHub Actions** ở mỗi push/PR (job *Gọi thật API ngoài*); kết quả nằm ở phần Summary của lần chạy và trong artifact `integrations-evidence` (file JSON);
- trên máy chủ, **7:00 mỗi sáng** (scheduler), ghi vào `storage/logs/integrations-*.log`.

## Lần chạy trên GitHub Actions, 28/11/2026 09:02 (giờ Việt Nam)

Job: https://github.com/NgDuyBaoo/NgoiBooking/actions/runs/46096023184/job/140827493993 (runner `ubuntu-latest` của GitHub, gọi Internet thật)

```
+------------------------------+---------+-----------+----------------------------------------------------+
| API                          | Kết quả | Thời gian | Chi tiết                                           |
+------------------------------+---------+-----------+----------------------------------------------------+
| Open-Meteo (thời tiết Huế)   | OK      | 159 ms    | 🌧️ Mưa rào, 24°C (23–24°C), mưa 100%               |
| VietQR – danh sách ngân hàng | OK      | 115 ms    | 76 ngân hàng; ngân hàng của tiệm: Vietcombank      |
| VietQR – ảnh QR (Quick Link) | OK      | 188 ms    | image/svg+xml; charset=utf-8, 45531 byte           |
| VietQR – tạo QR có khóa API  | BỎ QUA  | 0 ms      | Bỏ qua: chưa đặt VIETQR_CLIENT_ID / VIETQR_API_KEY |
+------------------------------+---------+-----------+----------------------------------------------------+
```

## Các API đang dùng

| API | Dùng để | Xác thực | Code |
|---|---|---|---|
| Open-Meteo `GET /v1/forecast` | Thời tiết Huế trên trang chủ và bước chọn giờ | Không cần khóa | `app/Services/WeatherService.php` |
| VietQR `GET /v2/banks` | Tên, logo ngân hàng nhận tiền cạnh mã QR | Không cần khóa | `app/Services/BankDirectory.php` |
| VietQR Quick Link `img.vietqr.io` | Ảnh QR chuyển khoản khi chưa có khóa | Không cần khóa | `app/Services/VietQr.php` |
| VietQR `POST /v2/generate` | Tạo QR chuyển khoản qua API chính thức | Header `x-client-id`, `x-api-key` (`VIETQR_CLIENT_ID`, `VIETQR_API_KEY`) | `app/Services/VietQr.php` |

Để kiểm cả API có khóa trên CI: đăng ký miễn phí tại https://my.vietqr.io, rồi thêm 2 secret `VIETQR_CLIENT_ID` và `VIETQR_API_KEY` trong *Settings → Secrets and variables → Actions* của repo.

## Zalo ZNS (thông báo khách)

Tin xác nhận, nhắc giờ chụp và báo hủy lịch gửi khách qua Zalo ZNS, thay cho email. Cần OA và mẫu tin của tiệm nên không chạy trong `ngoi:check-integrations`; test dùng `Http::fake`. Cài đặt và các việc phải tự làm: [zalo-zns.md](zalo-zns.md).
