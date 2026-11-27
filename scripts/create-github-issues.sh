#!/usr/bin/env bash
# Tạo nhãn + các Issue theo backlog dự án trên GitHub (cần GitHub CLI: gh auth login).
#   scripts/create-github-issues.sh <owner>/<repo>
# Issue đã hoàn thành được đóng ngay kèm ghi chú, để lịch sử quản lý công việc đầy đủ.
set -euo pipefail
REPO="${1:?Dùng: $0 owner/repo}"

label() { gh label create "$1" --repo "$REPO" --color "$2" --description "$3" --force >/dev/null; }
label feature 7A4A2B "Chức năng mới"
label bug d73a4a "Lỗi"
label frontend F3DDBB "Blade / Livewire / Tailwind"
label backend 3B2416 "API, service, database"
label devops 0e8a16 "CI/CD, Docker, giám sát"
label docs c5def5 "Tài liệu"
label "cần phân loại" fbca04 "Issue mới chưa phân loại"

# tiêu đề | nhãn | trạng thái (done/open) | mô tả
while IFS='|' read -r title labels state body; do
  [ -z "$title" ] && continue
  url=$(gh issue create --repo "$REPO" --title "$title" --label "$labels" --body "$body")
  echo "$url"
  if [ "$state" = "done" ]; then gh issue close "$url" --comment "Đã hoàn thành trên nhánh main." >/dev/null; fi
done <<'LIST'
Khởi tạo dự án Laravel 12, cấu trúc repo backend/frontend|devops|done|Tạo Laravel 12, cài Livewire, Sanctum, Scramble, Telescope, Pulse, Pest. Cấu trúc thư mục theo docs/kien-truc.md.
Thiết kế database và migration|backend|done|Bảng branches, rooms, access_codes, bookings, messages; chỉ số UNIQUE chống đặt trùng (BR-06). Xem docs/database.md.
Seeder và factory dữ liệu mẫu|backend|done|Nạp data/catalog, tài khoản owner/staff/khách, ~2.600 booking 30 ngày để có số liệu thống kê.
Đăng ký, đăng nhập, đăng xuất (web)|frontend,feature|done|Form Blade có @csrf, FormRequest kiểm tra dữ liệu, khóa sau 5 lần sai. FR-A01.
Mã truy cập 6 số đổi mỗi ngày theo giờ VN|backend,feature|done|BR-01, BR-02. Admin xem và tạo mã mới.
Luồng đặt lịch 6 bước (Livewire)|frontend,feature|done|FR-U01..FR-U06. Slot 10 phút 08:30–22:30, chỉ đặt trong ngày.
Lịch của tôi và khách tự hủy|frontend,feature|done|FR-U07, BR-10.
Admin: lưới lịch phòng, walk-in, check-in/hủy/hoàn thành|frontend,feature|done|FR-A03..FR-A07.
Admin: thống kê và báo cáo|frontend,feature|done|FR-A08: lượt đặt, doanh thu theo ngày, hiệu suất phòng, theo chi nhánh, xuất CSV.
REST API resource controller + Sanctum|backend,feature|done|CRUD chi nhánh, phòng, booking, lời nhắn. Token Sanctum có ability staff.
Tài liệu API bằng Scramble|backend,docs|done|/docs/api, xuất docs/api/openapi.json.
Tích hợp API ngoài: Open-Meteo, VietQR|backend,feature|done|Thời tiết Huế trên trang đặt lịch, mã QR chuyển khoản theo mã booking.
Telescope, Pulse, log, cảnh báo truy vấn chậm|devops|done|Xem docs/giam-sat.md.
Queue gửi email xác nhận, scheduler tự hủy booking trễ|backend,feature|done|BR-07: quá 15 phút chưa check-in thì hủy.
Cache, eager loading, chống N+1|backend|done|Xem docs/hieu-nang.md.
GitHub Actions: lint, test SQLite + MySQL, build Docker|devops|done|.github/workflows/ci.yml
Triển khai lên máy chủ thật + HTTPS|devops|open|Dùng deploy/docker-compose.yml, cấu hình domain và SMTP.
Kiểm thử thực tế tại 2 chi nhánh (UAT)|docs|open|Ghi nhận phản hồi nhân viên và khách.
Gửi link ảnh cho khách sau buổi chụp|feature|open|Phiên bản 1.1.
LIST
