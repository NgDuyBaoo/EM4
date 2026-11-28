# Workflow dự án

Tài liệu mô tả **quy trình nghiệp vụ** (khách và admin dùng hệ thống thế nào) và **quy trình làm việc của nhóm**
(chia việc, Git, review, CI/CD, triển khai). Sơ đồ tổng: `docs/assets/system-workflow.jpg`.

---

## Phần A. Quy trình nghiệp vụ

### A1. Khách đặt lịch (6 bước)

```mermaid
flowchart LR
    S0["0. Đăng ký / đăng nhập"] --> S1["1. Nhập mã truy cập<br/>6 số, đổi mỗi ngày"] --> S2["2. Chọn chi nhánh<br/>1 trong 2"]
    S2 --> S3["3. Chọn phòng<br/>Vintage / Classic / Film"]
    S3 --> S4["4. Chọn khung giờ<br/>slot 10 phút 08:30–22:30"]
    S4 --> S5["5. Xác nhận<br/>tên + SĐT"]
    S5 -->|OK| S6["6. Thành công<br/>mã booking NGxxxxxx"]
    S5 -->|Slot vừa bị đặt| S4
```

### A2. Admin quản lý (4 bước)

```mermaid
flowchart LR
    A1["1. Đăng nhập"] --> A2["2. Xem lịch phòng<br/>theo ngày, chi nhánh"]
    A2 --> A3["3. Quản lý booking<br/>check-in · hủy · hoàn thành"]
    A3 --> A4["4. Thống kê & báo cáo<br/>lượt đặt, doanh thu, hiệu suất phòng"]
```

### A3. Một ngày vận hành tại cửa hàng
1. **08:00** Nhân viên đăng nhập admin, xem mã truy cập hôm nay, ghi lên bảng tại quầy / màn hình.
2. **Trong ngày** Khách quét/đọc mã → đặt online. Khách vãng lai → nhân viên bấm ô trống tạo walk-in.
3. Khách đến → tìm theo mã booking → **Check-in**. Chụp xong → **Hoàn thành**.
4. Khách trễ quá giờ hẹn → **Hủy vì trễ giờ** để nhường slot.
5. **Cuối ngày** Xem Thống kê hôm nay, xuất CSV nếu cần đối soát.
6. **Tự động** Mỗi 5 phút hệ thống hủy booking quá 15 phút chưa check-in; tin Zalo xác nhận, báo hủy và nhắc giờ chụp gửi khách qua hàng đợi (không dùng email).
7. **00:00** Hệ thống tự dùng mã mới cho ngày hôm sau.

---

## Phần B. Quy trình làm việc của nhóm

### B1. Vai trò

| Vai trò | Phụ trách |
|---------|-----------|
| Trưởng nhóm / PO | Đặc tả (`docs/dac-ta-yeu-cau.md`), tạo và ưu tiên Issue, duyệt PR vào `main` |
| Frontend | `frontend/resources` (Blade, Livewire view, Tailwind), `design/` |
| Backend | `backend/app`, `backend/database`, `backend/routes`, API |
| DevOps / Tester | `.github/workflows`, `deploy/`, `scripts/`, test Pest, giám sát |

### B2. Quản lý công việc bằng GitHub Issues
- Mỗi chức năng/lỗi là 1 Issue, dùng mẫu trong `.github/ISSUE_TEMPLATE` (ghi mã yêu cầu FR/BR).
- Nhãn: `feature`, `bug`, `frontend`, `backend`, `devops`, `docs`. Issue mới chưa nhãn được workflow `issue-triage.yml` gắn `cần phân loại`.
- Bảng GitHub Projects: cột *Todo → In progress → Review → Done*. PR ghi `Closes #<số>` để Issue tự đóng khi merge.
- Backlog ban đầu tạo bằng `scripts/create-github-issues.sh owner/repo` (xem `docs/huong-dan-github.md`).

### B3. Nhánh Git

```mermaid
gitGraph
    commit id: "init"
    branch develop
    checkout develop
    branch feature/12-booking-wizard
    commit id: "feat(web): luồng đặt lịch"
    checkout develop
    merge feature/12-booking-wizard
    branch feature/15-api-sanctum
    commit id: "feat(api): resource controller"
    checkout develop
    merge feature/15-api-sanctum
    checkout main
    merge develop tag: "v1.0.0"
```

- `main`: bản chạy thật, chỉ nhận merge từ `develop` hoặc `hotfix/*`, mỗi lần gắn tag `vX.Y.Z` (workflow Release đẩy image).
- `develop`: bản tích hợp.
- `feature/<số-issue>-<mô-tả>`, `fix/<số-issue>-<mô-tả>`, `hotfix/<mô-tả>`.
- Bảo vệ nhánh `main`, `develop`: bắt buộc PR, CI xanh, ≥ 1 review.

### B4. Quy ước commit (Conventional Commits)

```
<type>(<phạm vi>): <mô tả ngắn, thì hiện tại>

<giải thích vì sao, nếu cần>

Closes #12
```

`type`: `feat`, `fix`, `docs`, `test`, `refactor`, `perf`, `chore`, `ci`. Phạm vi: `web`, `api`, `db`, `booking`, `admin`, `ci`…
Ví dụ: `feat(booking): chặn đặt slot đã qua giờ (BR-08)`, `fix(api): trả 409 khi đặt trùng`, `test(web): thêm test lịch của tôi`.
Mỗi commit một thay đổi trọn vẹn, chạy được test.

### B5. Quy trình một chức năng

```mermaid
flowchart TD
    I["Issue (mẫu feature)<br/>gắn mã FR/BR"] --> B["Nhánh feature/&lt;số&gt;-…"]
    B --> C["Code + test Pest"]
    C --> L["scripts/test.sh (Pint + Pest)"]
    L --> P["Pull Request vào develop<br/>Closes #số"]
    P --> CI{"GitHub Actions<br/>Pint · Pest SQLite + MySQL · Scramble · Docker"}
    CI -->|Lỗi| C
    CI -->|Đạt| R["Review"]
    R -->|Cần sửa| C
    R -->|Duyệt| M["Squash merge → Issue tự đóng"]
    M --> REL["develop → main, tag vX.Y.Z → Release"]
```

### B6. GitHub Actions (`.github/workflows`)

| Workflow | Khi chạy | Việc |
|----------|---------|------|
| `ci.yml` | Push / PR vào `main`, `develop` | **lint** (Pint) → **test** (Pest, ma trận SQLite + MySQL 8.4, có coverage, kiểm tra `migrate:fresh --seed`) · **api-docs** (Scramble analyze + export) → **docker** (build image) |
| `release.yml` | Gắn tag `v*.*.*` | Build và đẩy image lên GHCR, tạo GitHub Release |
| `issue-triage.yml` | Mở Issue | Gắn nhãn `cần phân loại` nếu chưa có |

### B7. Definition of Done
- Đúng tiêu chí trong đặc tả / Issue; có test Pest; CI xanh; Pint không lỗi.
- Giao diện chạy được trên điện thoại 360px và máy tính.
- Cập nhật `docs/` nếu đổi API, bảng, quy tắc.

### B8. Tiến độ

| Hạng mục | Trạng thái |
|----------|-----------|
| Cấu trúc dự án Laravel, CI | ✅ |
| Migration, seeder, factory | ✅ |
| Đăng ký / đăng nhập, mã truy cập 6 số | ✅ |
| Đặt lịch 6 bước, lịch của tôi | ✅ |
| Admin: lịch phòng, booking, thống kê, phòng, lời nhắn | ✅ |
| REST API + Sanctum + Scramble | ✅ |
| Telescope, Pulse, log, cảnh báo query chậm | ✅ |
| Queue email, scheduler tự hủy, cache | ✅ |
| Triển khai máy chủ thật, HTTPS, SMTP | ⏳ |
| UAT tại 2 chi nhánh | ⏳ |
