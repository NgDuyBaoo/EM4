# REST API

- Tài liệu tương tác (Scramble): **`/docs/api`** khi chạy ứng dụng. Bản OpenAPI 3.1: [`docs/api/openapi.json`](api/openapi.json).
- Xác thực: **Laravel Sanctum**. `POST /api/auth/login` → `token`, gửi `Authorization: Bearer <token>`.
- Lỗi luôn là JSON: `{ "message": "...", "code": "SLOT_TAKEN", "details": {...} }`, lỗi kiểm tra dữ liệu có thêm `errors`.

| Method | Đường dẫn | Quyền | Controller |
|--------|-----------|-------|-----------|
| POST | `/api/auth/register` · `/api/auth/login` | – | AuthController |
| GET · POST | `/api/auth/me` · `/api/auth/logout` | token | AuthController |
| GET | `/api/branches`, `/api/branches/{id}` | – | BranchController (resource) |
| POST · PUT · DELETE | `/api/branches[/{id}]` | owner | BranchController |
| GET | `/api/branches/{id}/availability` | token + `X-Access-Code` | BranchController |
| GET | `/api/rooms`, `/api/rooms/{id}` | – | RoomController (resource) |
| POST · PUT · DELETE | `/api/rooms[/{id}]` | owner | RoomController |
| GET · POST · GET · PATCH · DELETE | `/api/bookings[/{id}]` | token (Policy) | BookingController (resource) |
| POST | `/api/messages` | – | MessageController |
| GET · GET · PATCH · DELETE | `/api/admin/messages[/{id}]` | staff | MessageController (resource) |
| GET · POST | `/api/admin/access-code[/regenerate]` | staff | AdminController |
| GET | `/api/admin/reports/summary?from&to&branch_id` | staff | AdminController |
| GET | `/api/health` · `/api/weather` | – | SystemController |

## Ví dụ

```bash
TOKEN=$(curl -s -X POST localhost:8000/api/auth/login -H 'Accept: application/json' \
  -d email=khach@ngoi.vn -d password=password | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')

curl -s localhost:8000/api/branches/1/availability -H "Authorization: Bearer $TOKEN" -H 'X-Access-Code: 123456'

curl -s -X POST localhost:8000/api/bookings -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"branch_id":1,"access_code":"123456","customer_name":"Nguyễn A","customer_phone":"0912345678",
       "items":[{"room_id":1,"time":"14:30"}]}'
```

| HTTP | Mã | Khi nào |
|------|----|---------|
| 409 | `SLOT_TAKEN` | Slot vừa có người đặt |
| 409 | `SLOT_PAST` | Slot đã bắt đầu |
| 409 | `INVALID_TRANSITION` | Đổi trạng thái sai vòng đời |
| 409 | `NOT_CANCELLABLE` | Khách hủy khi đã qua giờ |
| 422 | `INVALID_ACCESS_CODE`, `INVALID_SLOT`, `INVALID_ROOM`, `VALIDATION_ERROR` | Dữ liệu sai |
| 401 / 403 | `UNAUTHENTICATED` / `FORBIDDEN` | Thiếu token / không đủ quyền |
| 429 | – | Gửi quá nhiều request |
