# Ảnh minh chứng

Ảnh chụp để trong thư mục này, đặt tên theo bảng dưới và nhắc tới trong `docs/doi-chieu-tieu-chi.md`.

| File | Mục | Cách chụp |
|---|---|---|
| `pulse-dashboard.png` | 5d | Chạy `docker compose -f deploy/docker-compose.yml up -d`, đăng nhập tài khoản chủ cửa hàng, mở `/pulse` (thẻ Servers và thẻ **Mạng** có số liệu) |
| `server-alert-email.png` | 5d | Đặt tạm `NGOI_ALERT_CPU_PERCENT=1` cho container `pulse`, chờ 1 phút, chụp email (hoặc Slack) "Cảnh báo máy chủ" |
| `error-alert-email.png` | 5b | Gây một lỗi 500 thử (ví dụ tạm tắt MySQL rồi mở trang quản lý), chụp email "Lỗi hệ thống" |
| `slow-query-alert.png` | 5c | Đặt tạm `NGOI_SLOW_QUERY_MS=1`, mở trang thống kê, chụp email "Cảnh báo truy vấn chậm" |
| `scramble-docs.png` | 4c | Mở `/docs/api` |
| `telescope.png` | 5a | Mở `/telescope/requests` |
| `github-actions.png` | 1d | Tab Actions, một lần chạy CI xanh đủ các job |

Khi chụp xong nhớ trả các biến môi trường về giá trị cũ.
