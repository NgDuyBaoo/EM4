# Ảnh minh chứng

Ảnh chụp để trong thư mục này, đặt tên theo bảng dưới và nhắc tới trong `docs/doi-chieu-tieu-chi.md`.

| File | Mục | Trạng thái | Cách chụp |
|---|---|---|---|
| `pulse-dashboard.png` | 5d | ✅ Đã có | Chạy `docker compose -f deploy/docker-compose.yml up -d`, đăng nhập tài khoản chủ cửa hàng, mở `/pulse` (thẻ Servers và thẻ **Mạng** có số liệu) |
| `server-alert-email.png` | 5d | Chưa có | Đặt tạm `NGOI_ALERT_CPU_PERCENT=1` cho container `pulse`, chờ 1 phút, chụp email (hoặc Slack) "Cảnh báo máy chủ" |
| `error-alert-email.png` | 5b | Chưa có | Gây một lỗi 500 thử (ví dụ tạm tắt MySQL rồi mở trang quản lý), chụp email "Lỗi hệ thống" |
| `slow-query-alert.png` | 5c | Chưa có | Đặt tạm `NGOI_SLOW_QUERY_MS=1`, mở trang thống kê, chụp email "Cảnh báo truy vấn chậm" |
| `scramble-docs.png` | 4c | ✅ Đã có | Mở `/docs/api` |
| `scramble-endpoint.png` | 4c | ✅ Đã có | Mở một endpoint trong `/docs/api` (ví dụ GET /bookings) |
| `github-pr-review.png` | 1d | ✅ Đã có | Trang PR có Approve của thành viên, CI xanh |
| `telescope.png` | 5a | Chưa có | Mở `/telescope/requests` |
| `github-actions.png` | 1d | Chưa có | Tab Actions, một lần chạy CI xanh đủ các job |

Khi chụp xong nhớ trả các biến môi trường về giá trị cũ.
