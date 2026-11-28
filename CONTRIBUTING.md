# Quy trình đóng góp

## 1. Bắt đầu từ Issue

Mỗi việc là một Issue có: mô tả, **Tiêu chí hoàn thành** (checklist), nhãn, người nhận.

## 2. Nhánh chủ đề

Tách từ `main`, đặt tên theo loại việc và số issue:

```
feat/38-ten-ngan    fix/40-ten-ngan    docs/41-ten-ngan    test/…    perf/…    ci/…
```

Không commit thẳng lên `main`.

## 3. Commit

Theo [Conventional Commits](https://www.conventionalcommits.org/), **ghi số issue ở cuối tiêu đề**, mỗi commit một thay đổi:

```
feat(booking): hiện ngân hàng nhận tiền cạnh mã QR (#22)
fix(api): đổi trạng thái booking chỉ khi token có quyền staff (#30)
```

`type`: `feat`, `fix`, `docs`, `test`, `refactor`, `perf`, `ci`, `chore`.

## 4. Pull Request

- Tiêu đề giống commit chính, có số issue.
- Thân PR theo mẫu `.github/pull_request_template.md`, có `Closes #n` để tự đóng Issue khi merge.
- CI (Pint, Pest SQLite + MySQL, Scramble, API ngoài, Docker) phải xanh.
- **Ít nhất 1 thành viên khác Approve** rồi mới merge. Người viết PR không tự merge khi chưa có review.
- Merge bằng *Create a merge commit* để giữ lịch sử từng commit.

## 5. Bảo vệ nhánh `main` (chủ repo bật một lần)

*Settings → Branches → Add branch ruleset* cho `main`:

- Require a pull request before merging, **Required approvals: 1**
- Require status checks to pass: chọn các job của workflow **CI**
- Block force pushes
